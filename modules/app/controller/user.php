<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of App_Controller_User
 *
 * @author Tomy
 */
class App_Controller_User extends Controller
{

    /**
     * 
     */
    public function login()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        if (RequestMethods::post('submitLogin')) {

            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('account_error', 'Email not provided');
                $error = true;
            }

            if (empty($password)) {
                $view->set('account_error', 'Password not provided');
                $error = true;
            }

            if (!$error) {
                try {
                    $security = Registry::get('security');
                    $status = $security->authenticate($email, $password);

                    if ($status) {
                        $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
                        $user->lastLogin = date('Y-m-d H:i:s', time());
                        $user->save();

                        self::redirect('/');
                    } else {
                        $view->set('account_error', 'Email address and/or password are incorrect');
                    }
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', 'Unknown error occured');
                    }
                }
            }
        }
    }

    /**
     * 
     */
    public function logout()
    {
        $security = Registry::get('security');
        $security->logout();
        self::redirect('/');
    }

    /**
     * @before _secured, _projectmanager
     */
    public function index()
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $superAdmin = $security->isGranted('role_superadmin');

        $users = App_Model_User::all(
                        array(
                            'active = ?' => true,
                            'deleted = ?' => false,
                            'role <> ?' => 'role_superadmin'), 
                        array('id', 'firstname', 'lastname', 'email', 'role', 
                            'active', 'created', 'pwdExpire', 'lastLogin'), 
                        array('id' => 'asc')
        );

        $view->set('users', $users)
                ->set('superadmin', $superAdmin);
    }
    
    /**
     * @before _secured, _projectmanager
     */
    public function add()
    {
        $security = Registry::get('security');
        $view = $this->getActionView();

        $errors = array();
        $superAdmin = $security->isGranted('role_superadmin');
        $roles = array_keys($security->getRoleManager()->getRoles());
        $clients = App_Model_Client::all(array('active = ?' => true));
        
        $view->set('superadmin', $superAdmin)
                ->set('clients', $clients)
                ->set('roles', $roles);
        
        if (RequestMethods::post('submitAddUser')) {
            $this->checkToken();
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            $email = App_Model_User::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Email is already used');
            }

            $salt = $security->createSalt();
            $hash = $security->getSaltedHash(RequestMethods::post('password'), $salt);

            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'clientId' => RequestMethods::post('clientid'),
                'password' => $hash,
                'salt' => $salt,
                'role' => RequestMethods::post('role', 'role_client'),
                'phone' => RequestMethods::post('phone'),
                'pwdExpire' => date('Y-m-d H:i:s', time()+12*30*24*60*60),
                'taskStateFilter' => RequestMethods::post('taskStateFilter', 'a:0:{}'),
                'taskPriorityFilter' => RequestMethods::post('taskPriorityFilter', 'a:0:{}'),
                'projectStateFilter' => RequestMethods::post('projectStateFilter', 
                        'a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}'),
                'projectPriorityFilter' => RequestMethods::post('projectPriorityFilter', 
                        'a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}'),
            ));

            if (empty($errors) && $user->validate()) {
                $id = $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $id));
                $view->successMessage('Account has been successfully created');
                self::redirect('/user');
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('user', $user);
            }
        }
    }
    
    /**
     * @before _secured, _client
     */
    public function profile()
    {
        $view = $this->getActionView();
        if((int)$this->getUser()->getClientId() == 0){
            $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
        }else{
            $user = App_Model_User::fetchUserById($this->getUser()->getId());
        }

        if (NULL === $user) {
            $view->warningMessage('User not found');
            self::redirect('/user');
        }
        
        $assignedTasks = App_Model_User::fetchAssignedToTasks($user->getId());
        $assignedProjects = App_Model_User::fetchAssignedToProjects($user->getId());
        
        $view->set('user', $user)
            ->set('assignedProjects', $assignedProjects)
            ->set('assignedTasks', $assignedTasks);
        
    }
    
    /**
     * @before _secured, _client
     */
    public function updateProfile()
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $errors = array();
        $user = App_Model_User::first(
                array('active = ?' => true, 'deleted = ?' => false,'id = ?' => $this->getUser()->getId()));

        if (NULL === $user) {
            $view->warningMessage('User not found');
            self::redirect('/user');
        }
        
        if($user->getClientId() != 0){
            $client = App_Model_Client::first(
                    array('active = ?' => true, 'id = ?' => $user->getClientId()));
            
            if($client !== null){
                $view->set('client', $client);
            }
        }
        
        $view->set('user', $user);
        
        if (RequestMethods::post('submitUpdateProfile')) {
            $this->checkToken();
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            if (RequestMethods::post('email') != $user->getEmail()) {
                $email = App_Model_User::first(
                            array('email = ?' => RequestMethods::post('email', $user->getEmail())), 
                            array('email')
                );
                
                if ($email) {
                    $errors['email'] = array('Email is already used');
                }
            }

            $pass = RequestMethods::post('password');
            
            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = $security->createSalt();
                $hash = $security->getSaltedHash($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->password = $hash;
            $user->salt = $salt;
            $user->phone = RequestMethods::post('phone');
            
            if(isset($client)){
                $client->contactPerson = RequestMethods::post('contperson');
                $client->contactEmail = RequestMethods::post('contemail');
                $client->companyName = RequestMethods::post('compname');
                $client->companyAddress = RequestMethods::post('address');
                $client->contactPhone = RequestMethods::post('contphone');
                $client->www = RequestMethods::post('compwww');
                
                if($client->validate()){
                    $client->save();
                    Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                }else{
                    Event::fire('app.log', array('fail', 'Client id: ' . $client->getId()));
                    $errors = $errors + $client->getErrors();
                }
            }

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/user');
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $user->getId()));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }
    
    /**
     * @before _secured, _developer
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $user = App_Model_User::first(array('id = ?' => (int)$id));
        
        if((int)$user->getClientId() != 0){
            $user = App_Model_User::fetchUserById($user->getId());
        }

        if (NULL === $user) {
            $view->warningMessage('User not found');
            self::redirect('/user');
        }
        
        $assignedTasks = App_Model_User::fetchAssignedToTasks($user->getId());
        $assignedProjects = App_Model_User::fetchAssignedToProjects($user->getId());
        
        $view->set('user', $user)
            ->set('assignedProjects', $assignedProjects)
            ->set('assignedTasks', $assignedTasks);
        
    }
    
    /**
     * @before _secured, _projectmanager
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $errors = array();
        $superAdmin = $security->isGranted('role_superadmin');
        $user = App_Model_User::first(
                array('active = ?' => true, 'deleted = ?' => false,'id = ?' => $id));

        if (NULL === $user) {
            $view->warningMessage('User not found');
            self::redirect('/user');
        } elseif ($user->role == 'role_superadmin' && !$superAdmin) {
            $view->warningMessage('You dont have permissions to update this user');
            self::redirect('/user');
        }
        
        $roles = array_keys($security->getRoleManager()->getRoles());
        $clients = App_Model_Client::all(array('active = ?' => true));
        
        $view->set('superadmin', $superAdmin)
                ->set('clients', $clients)
                ->set('user', $user)
                ->set('roles', $roles);
        
        if (RequestMethods::post('submitEditUser')) {
            $this->checkToken();
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                            array('email = ?' => RequestMethods::post('email', $user->email)), 
                            array('email')
                );
                
                if ($email) {
                    $errors['email'] = array('Email is already used');
                }
            }

            $pass = RequestMethods::post('password');
            
            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = $security->createSalt();
                $hash = $security->getSaltedHash($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->clientId = RequestMethods::post('clientid');
            $user->password = $hash;
            $user->salt = $salt;
            $user->role = RequestMethods::post('role');
            $user->active = RequestMethods::post('active');
            $user->phone = RequestMethods::post('phone');

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/user');
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $id));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }
    
    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        $user = App_Model_User::first(
                array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));
        
        if($user === null){
            $view->warningMessage('User not found');
            self::redirect('/user');
        }
        
        $view->set('user', $user);
        
        if(RequestMethods::post('submitDeleteUser')){
            $this->checkToken();
            $user->deleted = true;
            
            if($user->validate()){
                $user->save();
                
                App_Model_ProjectUser::deleteAll(array('userId = ?' => (int)$id));
                $taskUser = App_Model_Task::all(array('assignedTo = ?' => (int)$id));
                
                foreach ($taskUser as $task) {
                    $task->assignedTo = 1;
                    $task->save();
                }
                
                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage('User has been deleted successfully');
                self::redirect('/user');
            }else{
                Event::fire('app.log', array('fail', 'Project id: ' . $user->getId()));
                $view->errorMessage('An error occured while deleting the user');
                self::redirect('/user');
            }
        }
    }
    
    /**
     * @before _secured, _admin
     */
    public function undelete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $user = App_Model_User::first(array('id = ?' => (int) $id));

            if ($user === null) {
                echo 'User not found';
            }

            $user->deleted = false;

            if ($user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $user->getId()));
                echo 'An error occured while undeleting the user';
            }
        } else {
            echo 'Security token is not valid';
        }
    }

}
