<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Security\PasswordManager;

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

                    if ($status === true) {
                        self::redirect('/');
                    } else {
                        $view->set('account_error', 'Email address and/or password are incorrect');
                    }
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', 'Email address and/or password are incorrect');
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
                    'deleted = ?' => false,
                    'role <> ?' => 'role_superadmin'), 
                array('id', 'firstname', 'lastname', 'email', 
                    'role', 'active', 'created', 'lastLogin'), 
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
        $roles = array_keys($security->getAuthorization()->getRoleManager()->getRoles());
        $clients = App_Model_Client::all(array('active = ?' => true));

        $view->set('superadmin', $superAdmin)
                ->set('clients', $clients)
                ->set('roles', $roles);

        if (RequestMethods::post('submitAddUser')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/user');
            }

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            $email = App_Model_User::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Email is already used');
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);

            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'clientId' => RequestMethods::post('clientid', 0),
                'password' => $hash,
                'salt' => $salt,
                'role' => RequestMethods::post('role', 'role_client'),
                'phone' => RequestMethods::post('phone'),
                'taskStateFilter' => RequestMethods::post('taskStateFilter', 'a:0:{}'),
                'taskPriorityFilter' => RequestMethods::post('taskPriorityFilter', 'a:0:{}'),
                'projectStateFilter' => RequestMethods::post('projectStateFilter', 'a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}'),
                'projectPriorityFilter' => RequestMethods::post('projectPriorityFilter', 'a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}'),
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
        if ((int) $this->getUser()->getClientId() == 0) {
            $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
        } else {
            $user = App_Model_User::fetchUserById($this->getUser()->getId());
        }

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/user');
        }

        $assignedTasks = App_Model_User::fetchAssignedToTasks($user->getId());
        $assignedProjects = App_Model_User::fetchAssignedToProjects($user->getId());

        $selectedMonth = RequestMethods::issetpost('month') ? RequestMethods::post('month') : date('m');

        $daysOfMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, date('Y'));
        $days = array();

        for ($i = 1; $i <= $daysOfMonth; $i++) {
            $tm = mktime(0, 0, 0, $selectedMonth, $i, date('Y'));
            $days[$i] = array(
                'day' => date('d', $tm),
                'dayname' => date('D', $tm),
                'weekofyear' => date('W', $tm),
                'month' => date('F', $tm),
                'daysofmonth' => $daysOfMonth
            );
        }

        $timeLog = App_Model_User::fetchTimeLog($user->getId(), $selectedMonth);

        $view->set('user', $user)
                ->set('calendar', $days)
                ->set('assignedProjects', $assignedProjects)
                ->set('assignedTasks', $assignedTasks)
                ->set('timelog', $timeLog);
    }

    /**
     * @before _secured, _client
     */
    public function updateProfile()
    {
        $view = $this->getActionView();

        $errors = array();
        $user = App_Model_User::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => $this->getUser()->getId()));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/user');
        }

        if ($user->getClientId() != 0) {
            $client = App_Model_Client::first(
                            array('active = ?' => true, 'id = ?' => $user->getClientId()));

            if ($client !== null) {
                $view->set('client', $client);
            }
        }

        $view->set('user', $user);

        if (RequestMethods::post('submitUpdateProfile')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/user');
            }

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            if (RequestMethods::post('email') != $user->getEmail()) {
                $email = App_Model_User::first(
                                array('email = ?' => RequestMethods::post('email', $user->getEmail())), array('email')
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
                $salt = PasswordManager::createSalt();
                $hash = PasswordManager::hashPassword($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->password = $hash;
            $user->salt = $salt;
            $user->phone = RequestMethods::post('phone');

            if (isset($client)) {
                $client->contactPerson = RequestMethods::post('contperson');
                $client->contactEmail = RequestMethods::post('contemail');
                $client->companyName = RequestMethods::post('compname');
                $client->companyAddress = RequestMethods::post('address');
                $client->contactPhone = RequestMethods::post('contphone');
                $client->www = RequestMethods::post('compwww');

                if ($client->validate()) {
                    $client->save();
                    Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                } else {
                    Event::fire('app.log', array('fail', 'Client id: ' . $client->getId()));
                    $errors = $errors + $client->getErrors();
                }
            }

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/user');
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $user->getId()));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $user = App_Model_User::first(array('id = ?' => (int) $id));

        if ((int) $user->getClientId() != 0) {
            $user = App_Model_User::fetchUserById($user->getId());
        }

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/user');
        }

        $assignedTasks = App_Model_User::fetchAssignedToTasks($user->getId());
        $assignedProjects = App_Model_User::fetchAssignedToProjects($user->getId());

        $selectedMonth = RequestMethods::issetpost('month') ? RequestMethods::post('month') : date('m');

        $daysOfMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, date('Y'));
        $days = array();

        for ($i = 1; $i <= $daysOfMonth; $i++) {
            $tm = mktime(0, 0, 0, $selectedMonth, $i, date('Y'));
            $days[$i] = array(
                'day' => date('d', $tm),
                'dayname' => date('D', $tm),
                'weekofyear' => date('W', $tm),
                'month' => date('F', $tm),
                'daysofmonth' => $daysOfMonth
            );
        }

        $timeLog = App_Model_User::fetchTimeLog($user->getId(), $selectedMonth);

        $view->set('user', $user)
                ->set('timelog', $timeLog)
                ->set('calendar', $days)
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
        $user = App_Model_User::first(
                        array('deleted = ?' => false, 'id = ?' => $id));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/user');
        } elseif ($user->role == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            self::redirect('/user');
        }

        $roles = array_keys($security->getAuthorization()->getRoleManager()->getRoles());
        $clients = App_Model_Client::all(array('active = ?' => true));

        $view->set('clients', $clients)
                ->set('user', $user)
                ->set('roles', $roles);

        if (RequestMethods::post('submitEditUser')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/user');
            }

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), array('email')
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
                $salt = PasswordManager::createSalt();
                $hash = PasswordManager::hashPassword($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->clientId = RequestMethods::post('clientid');
            $user->password = $hash;
            $user->salt = $salt;
            $user->role = RequestMethods::post('role', $user->getRole());
            $user->active = RequestMethods::post('active');
            $user->phone = RequestMethods::post('phone');

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
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
                        array('deleted = ?' => false, 'id = ?' => (int) $id));

        if ($user === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/user');
        }

        $view->set('user', $user);

        if (RequestMethods::post('submitDeleteUser')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/user');
            }

            $user->deleted = true;

            if ($user->validate()) {
                $user->save();

                App_Model_ProjectUser::deleteAll(array('userId = ?' => (int) $id));
                $taskUser = App_Model_Task::all(array('assignedTo = ?' => (int) $id));

                foreach ($taskUser as $task) {
                    $task->assignedTo = 1;
                    $task->save();
                }

                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage('User has been deleted successfully');
                self::redirect('/user');
            } else {
                Event::fire('app.log', array('fail', 'Project id: ' . $user->getId()));
                $view->errorMessage(self::ERROR_MESSAGE_1);
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

        if ($this->checkCSRFToken()) {
            $user = App_Model_User::first(array('id = ?' => (int) $id));

            if ($user === null) {
                echo self::ERROR_MESSAGE_2;
            }

            $user->deleted = false;

            if ($user->validate()) {
                $user->save();

                Event::fire('app.log', array('success', 'User id: ' . $user->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $user->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
