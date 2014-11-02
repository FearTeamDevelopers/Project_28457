<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class App_Controller_Project extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Project::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @before _secured, _developer
     */
    public function index()
    {
        $view = $this->getActionView();
        $states = App_Model_State::all(array('type = ?' => 'project'));

        $projectFilterState = unserialize($this->getUser()->getProjectStateFilter());
        $projectFilterPrior = unserialize($this->getUser()->getProjectPriorityFilter());

        $projects = App_Model_Project::fetchProjectsWithBasicInfoByFilter($projectFilterState, $projectFilterPrior);

        $view->set('projects', $projects)
                ->set('states', $states);

        if (RequestMethods::post('projectFilterSubmit')) {
            $this->checkCSRFToken();
            
            $filterStates = (array) RequestMethods::post('projectStateFilterVal');
            $filterPriority = (array) RequestMethods::post('projectPriorFilterVal');

            $sessionUser = Registry::get('session')->get('authUser');
            $sessionUser->setProjectStateFilter(serialize($filterStates));
            $sessionUser->setProjectPriorityFilter(serialize($filterPriority));

            $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
            $user->setProjectStateFilter(serialize($filterStates));
            $user->setProjectPriorityFilter(serialize($filterPriority));

            if ($user->validate()) {
                $user->save();
                $view->successMessage('Filter has been successfully saved');
                self::redirect('/project');
            } else {
                $view->errorMessage('An error occured while saving filter');
                self::redirect('/project');
            }
        }
    }

    /**
     * @before _secured, _client
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        if (!$this->hasAccessToProject($id)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }

        $project = App_Model_Project::fetchProjectById($id);
        $allUsers = App_Model_User::all(array('active = ?' => true, 'deleted = ?' => false));
        $assignedUsers = $project->getAssignedUsers();

        if (!empty($assignedUsers)) {
            $assigneduserids = array();

            foreach ($assignedUsers as $auser) {
                $assigneduserids[] = $auser->getUserId();
            }
        }
        
        $nextStates = App_Model_State::all(
                array(
                    'active = ?' => true,
                    'type = ?' => 'project',
                    'previousState = ?' => $project->getStateId()
                ));

        $currency = $this->loadConfigFromDb('currency');
        
        $view->set('project', $project)
                ->set('currency', $currency)
                ->set('assigneduserids', $assigneduserids)
                ->set('allusers', $allUsers)
                ->set('nextstates', $nextStates);

        if (RequestMethods::post('submitSendMess')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            
            $chatMessage = new App_Model_ProjectChat(array(
                'projectId' => $project->getId(),
                'userId' => $this->getUser()->getId(),
                'isPublic' => RequestMethods::post('showto', 1),
                'title' => RequestMethods::post('messtitle'),
                'body' => RequestMethods::post('messtext')
            ));

            if ($chatMessage->validate()) {
                $messId = $chatMessage->save();

                Event::fire('app.log', array('success', 'Message id: ' . $messId));
                $view->successMessage('Message has been successfully saved');
                self::redirect('/project/' . $project->getUrlKey() . '/#chat');
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('message', $chatMessage)
                        ->set('errors', $chatMessage->getErrors());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function add()
    {
        $view = $this->getActionView();

        $managers = App_Model_User::fetchManagers();
        $clients = App_Model_Client::all(array('active = ?' => true));

        $view->set('managers', $managers)
                ->set('clients', $clients)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddProject')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/project');
            }
            $errors = array();
            
            $urlKey = $this->_createUrlKey(RequestMethods::post('projname'));
            
            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $project = new App_Model_Project(array(
                'managerId' => RequestMethods::post('manager'),
                'clientId' => RequestMethods::post('client'),
                'stateId' => 1,
                'title' => RequestMethods::post('projname'),
                'urlKey' => $urlKey,
                'description' => RequestMethods::post('projdesc'),
                'maxBudget' => RequestMethods::post('budget'),
                'gitRepository' => RequestMethods::post('repository'),
                'taskPrefix' => RequestMethods::post('taskprefix'),
                'nextTaskNumber' => 1,
                'plannedStart' => RequestMethods::post('plannedStart', date('Y-m-d')),
                'plannedEnd' => RequestMethods::post('plannedEnd'),
                'priority' => RequestMethods::post('priority', 1)
            ));

            if (empty($errors) && $project->validate()) {
                $prId = $project->save();

                $projectUser = new App_Model_ProjectUser(array(
                    'userId' => $project->getManagerId(),
                    'projectId' => $prId
                ));
                $projectUser->save();

                $assignUsers = App_Model_User::all(array('clientId = ?' => $project->getClientId()), array('id'));

                if (!empty($assignUsers)) {
                    foreach ($assignUsers as $user) {
                        $projectUser = new App_Model_ProjectUser(array(
                            'userId' => $user->getId(),
                            'projectId' => $prId
                        ));
                        $projectUser->validate();
                        $projectUser->save();
                    }
                }

                Event::fire('app.log', array('success', 'Project id: ' . $prId));
                $view->successMessage('Project has been successfully saved');
                self::redirect('/project/' . $project->getUrlKey() . '/');
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('project', $project)
                        ->set('errors', $errors + $project->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _client
     */
    public function uploadAttachment($id)
    {
        $view = $this->getActionView();
        
        if (!$this->hasAccessToProject($id)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }
        
        $project = App_Model_Project::first(
                array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int)$id));
        
        if($project === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }
        
        $view->set('projectid', $project->getId())
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('uploadFile')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/project');
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            $fileErrors = $fileManager->upload('files', 'pr-' . $project->getId(), time().'_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    $attachment = new App_Model_Attachment(array(
                        'userId' => $this->getUser()->getId(),
                        'filename' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'description' => RequestMethods::post('description'),
                        'size' => $file->getSize(),
                        'ext' => $file->getFormat(),
                        'path' => trim($file->getFilename(), '.'),
                    ));

                    if ($attachment->validate()) {
                        $aid = $attachment->save();

                        $prAttch = new App_Model_ProjectAttachment(array(
                            'projectId' => $project->getId(),
                            'attachmentId' => $aid
                        ));
                        $prAttch->save();

                        Event::fire('app.log', array('success', 'Attachment id: ' . $aid . ' in project ' . $project->getId()));
                    } else {
                        Event::fire('app.log', array('fail', 'Attachment in project ' . $project->getId()));
                        $errors['attachment'][] = $attachment->getErrors();
                    }
                }
            }
            
            if (empty($errors) && empty($fileErrors)) {
                $view->successMessage('Attachment has been successfully saved');
                self::redirect('/project/' . $project->getUrlKey() . '/#files');
            } else {
                $errors['files'] = $fileErrors;
                
                $view->set('errors', $errors)
                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        
        if (!$this->hasAccessToProject($id)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }
        
        $project = App_Model_Project::first(
                array('deleted = ?' => false, 'id = ?' => (int)$id));

        if($project === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }
        
        $managers = App_Model_User::fetchManagers();
        $clients = App_Model_Client::all(array('active = ?' => true));
        $states = App_Model_State::all(
                array(
                    'active = ?' => true,
                    'type = ?' => 'project'
                    ));
        
        $view->set('project', $project)
                ->set('states', $states)
                ->set('managers', $managers)
                ->set('clients', $clients);

        if (RequestMethods::post('submitEditProject')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            $errors = array();
            
            $urlKey = $this->_createUrlKey(RequestMethods::post('projname'));
            
            if($project->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }

            $project->managerId = RequestMethods::post('manager');
            $project->clientId = RequestMethods::post('client');
            $project->stateId = RequestMethods::post('state');
            $project->active = RequestMethods::post('active');
            $project->title = RequestMethods::post('projname');
            $project->urlKey = $urlKey;
            $project->description = RequestMethods::post('projdesc');
            $project->maxBudget = RequestMethods::post('budget');
            $project->isPaid = RequestMethods::post('paid');
            $project->gitRepository = RequestMethods::post('repository');
            $project->taskPrefix = RequestMethods::post('taskprefix');
            $project->plannedStart = RequestMethods::post('plannedStart', date('Y-m-d'));
            $project->plannedEnd = RequestMethods::post('plannedEnd');
            $project->priority = RequestMethods::post('priority', 1);

            if (empty($errors) && $project->validate()) {
                $project->save();

                Event::fire('app.log', array('success', 'Project id: ' . $project->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/project/' . $project->getUrlKey() . '/');
            } else {
                Event::fire('app.log', array('fail', 'Project id: ' . $project->getId()));
                $view->set('project', $project)
                        ->set('errors', $errors + $project->getErrors());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function assignUsers($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        if (!$this->hasAccessToProject($id)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }

        if (RequestMethods::post('performProjectUserAction')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            
            $errors = array();
            $uids = RequestMethods::post('projectusersids');

            $status = App_Model_ProjectUser::deleteAll(array('projectId = ?' => $id));

            if ($status != -1) {
                if ($uids[0] == '') {
                    self::redirect('/project');
                }
                
                $assignedIds = array();
                foreach ($uids as $userId) {
                    $projectUser = new App_Model_ProjectUser(array(
                        'userId' => $userId,
                        'projectId' => $id
                    ));

                    if ($projectUser->validate()) {
                        $projectUser->save();
                        $assignedIds[] = $userId;
                    } else {
                        $errors[] = $projectUser->getErrors();
                    }
                }

                if (empty($errors)) {
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    Event::fire('app.log', array('success', 'Assign user: ' . join(', ', $assignedIds). ' to project: '.$id));
                    self::redirect('/project/detail/' . $id . '#assignedUsers');
                } else {
                    Event::fire('app.log', array('fail', 'Assign user: ' . join(', ', $assignedIds). ' to project: '.$id));
                    $view->errorMessage(self::ERROR_MESSAGE_1);
                }
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function unassignUser($projectId, $userId)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
        if (!$this->hasAccessToProject($projectId)) {
            echo self::ERROR_MESSAGE_6;
            return;
        }
        
        if ($this->checkCSRFToken()) {
            $projectUser = App_Model_ProjectUser::first(
                            array(
                                'projectId = ?' => $projectId,
                                'userId = ?' => $userId
            ));

            if ($projectUser === null) {
                echo self::ERROR_MESSAGE_1;
            }

            if ($projectUser->delete()) {
                Event::fire('app.log', array('success', 'Unassign user: ' . $userId. ' from project: '.$projectId));
                echo 'User has been unassigned from project';
            } else {
                Event::fire('app.log', array('fail', 'Unassign user: ' . $userId. ' from project: '.$projectId));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _projectmanager
     * @param type $id
     */
    public function setProjectState($projectId, $state)
    {
        $view = $this->getActionView();

        if (!$this->hasAccessToProject($projectId)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }
        
        $project = App_Model_Project::first(
                array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $projectId));

        if ($project === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $project->stateId = (int)$state;
        if ($project->validate()) {
            $project->save();

            Event::fire('app.log', array('success', 'Project id: ' . $projectId. ' new state '. $state));
            $view->successMessage(self::SUCCESS_MESSAGE_2);
            self::redirect('/project/' . $project->getUrlKey() . '/');
        } else {
            Event::fire('app.log', array('fail', 'Project id: ' . $projectId. ' new state '. $state));
            $view->warningMessage('Project state could not be updated');
            self::redirect('/project/' . $project->getUrlKey() . '/');
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        if (!$this->hasAccessToProject($id)) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            self::redirect('/');
        }
        
        $project = App_Model_Project::first(
                array('deleted = ?' => false, 'id = ?' => (int) $id));
        
        if($project === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }
        
        $view->set('project', $project);
        
        if(RequestMethods::post('submitDeleteProject')){
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            $project->deleted = true;
            
            if($project->validate()){
                $project->save();
                
                Event::fire('app.log', array('success', 'Project id: ' . $project->getId()));
                $view->successMessage('Project has been deleted successfully');
                self::redirect('/project');
            }else{
                Event::fire('app.log', array('fail', 'Project id: ' . $project->getId()));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/project');
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
            $project = App_Model_Project::first(array('id = ?' => (int) $id));

            if ($project === null) {
                echo self::ERROR_MESSAGE_2;
            }

            $project->deleted = false;

            if ($project->validate()) {
                $project->save();

                Event::fire('app.log', array('success', 'Project id: ' . $project->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Project id: ' . $project->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();
        $errorsIds = array();

        if (RequestMethods::post('performProjectAction')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            
            $ids = RequestMethods::post('projectsids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'activate':
                    $projects = App_Model_Project::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $projects) {
                        foreach ($projects as $project) {
                            $project->active = true;
                            if ($project->validate()) {
                                $project->save();
                            }else{
                                $errors[] = 'An error occured while activating ' . $project->getTitle();
                                $errorsIds [] = $project->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('app.log', array('activate success', 'Project ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_8);
                    } else {
                        Event::fire('app.log', array('activate fail', 'Project ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/project');
                    break;
                case 'deactivate':
                    $projects = App_Model_Project::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $projects) {
                        foreach ($projects as $project) {
                            $project->active = false;
                            if ($project->validate()) {
                                $project->save();
                            }else{
                                $errors[] = 'An error occured while deactivating ' . $project->getTitle();
                                $errorsIds [] = $project->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('app.log', array('deactivate success', 'Project ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_8);
                    } else {
                        Event::fire('app.log', array('deactivate fail', 'Project ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/project');
                    break;
                default:
                    self::redirect('/project');
                    break;
            }
        }
    }

}
