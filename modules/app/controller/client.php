<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;

/**
 * Description of App_Controller_Client
 *
 * @author Tomy
 */
class App_Controller_Client extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Task::first(array('urlKey = ?' => $key));

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
        
        $clients = App_Model_Client::all(array('active = ?' => true));
        
        $view->set('clients', $clients);
    }

    /**
     * @before _secured, _developer
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $client = App_Model_Client::first(
                array('active = ?' => true, 'id = ?' => (int)$id));
        
        if($client === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }
        
        $clientProjects = App_Model_Project::fetchProjectsByClientId($client->getId());
        
        $view->set('client', $client)
            ->set('clientprojects', $clientProjects);
    }
    
    /**
     * @before _secured, _projectmanager
     */
    public function add()
    {
        $view = $this->getActionView();
        
        if(RequestMethods::post('submitAddClient')){
            if($this->checkCSRFToken() !== true){
                self::redirect('/client');
            }
            
            $client = new App_Model_Client(array(
                'contactPerson' => RequestMethods::post('contperson'),
                'contactEmail' => RequestMethods::post('contemail'),
                'companyName' => RequestMethods::post('compname'),
                'companyAddress' => RequestMethods::post('compaddress'),
                'contactPhone' => RequestMethods::post('contphone'),
                'www' => RequestMethods::post('www')
            ));
            
            if($client->validate()){
                $cid = $client->save();
                        
                Event::fire('app.log', array('success', 'Client id: ' . $cid));
                $view->successMessage('Client has been successfully created');
                self::redirect('/client');
            }else{
                Event::fire('app.log', array('fail'));
                $view->set('errors', $client->getErrors())
                        ->set('client', $client);
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        
        $client = App_Model_Client::first(array('id = ?'=> (int)$id));
        
        if($client === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/client');
        }
        
        $view->set('client', $client);
        
        if(RequestMethods::post('submitEditClient')){
            if($this->checkCSRFToken() !== true){
                self::redirect('/client');
            }
            
            $client->contactPerson = RequestMethods::post('contperson');
            $client->contactEmail = RequestMethods::post('contemail');
            $client->companyName = RequestMethods::post('compname');
            $client->companyAddress = RequestMethods::post('compaddress');
            $client->contactPhone = RequestMethods::post('contphone');
            $client->www = RequestMethods::post('www');
            
            if($client->validate()){
                $client->save();
                
                Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/client');
            }else{
                Event::fire('app.log', array('fail', 'Client id: ' . $client->getId()));
                $view->set('errors', $client->getErrors());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $client = App_Model_Client::first(array('id = ?' => (int) $id));

        if ($client === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/client');
        }

        $view->set('client', $client);

        if (RequestMethods::post('submitDeleteClient')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/client');
            }

            if ($client->delete()) {
                Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                $view->successMessage('Client has been deleted successfully');
                self::redirect('/client');
            } else {
                Event::fire('app.log', array('fail', 'Client id: ' . $client->getId()));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/client');
            }
        }
    }
    
    /**
     * @before _secured, _client
     */
    public function reportIssue()
    {
        $view = $this->getActionView();
        
        $clientProjects = App_Model_Project::fetchProjectsByClientId($this->getUser()->getClientId());
        
        $view->set('projects', $clientProjects)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitReportIssue')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/client');
            }
            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            $project  = App_Model_Project::first(
                    array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) RequestMethods::post('project')));
            
            if ($project === null) {
                $view->warningMessage(self::ERROR_MESSAGE_2);
                self::redirect('/client');
            }

            $urlKey = $project->getTaskPrefix().'-'.$project->getNextTaskNumber();
            
            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This task already exists');
            }

            $task = new App_Model_Task(array(
                'stateId' => 8,
                'projectId' => RequestMethods::post('project'),
                'createdBy' => $this->getUser()->getId(),
                'assignedTo' => $this->getUser()->getId(),
                'urlKey' => $urlKey,
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'taskType' => RequestMethods::post('type'),
                'priority' => RequestMethods::post('priority'),
                'spentTimeTotal' => ''
            ));

            if (empty($errors) && $task->validate()) {
                $tid = $task->save();

                $fileErrors = $fileManager->upload('files', 'pr-' . $task->getProjectId() . '/tk-' . $task->getId(), time() . '_')->getUploadErrors();
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

                            $prAttch = new App_Model_TaskAttachment(array(
                                'taskId' => $tid,
                                'attachmentId' => $aid
                            ));
                            $prAttch->save();

                            Event::fire('app.log', array('success', 'Attachment id: ' . $aid . 'for task ' . $tid . ' in project ' . $project->getId()));
                        } else {
                            $errors['attachment'][] = $attachment->getErrors();
                        }
                    }
                }
                
                if (empty($errors) && empty($fileErrors)) {
                    Event::fire('app.log', array('success', 'Issue id: ' . $tid));
                    $view->successMessage('Issue has been successfully reported');
                    self::redirect('/');
                } else {
                    $errors['files'] = $fileErrors;
                    Event::fire('app.log', array('fail', 'Attachment for task in project ' . $project->getId()));
                    $view->set('errors', $errors)
                            ->set('task', $task)
                            ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
                }
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('errors', $task->getErrors())
                        ->set('task', $task)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

}
