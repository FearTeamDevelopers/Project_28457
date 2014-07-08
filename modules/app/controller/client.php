<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

/**
 * Description of App_Controller_Client
 *
 * @author Tomy
 */
class App_Controller_Client extends Controller
{

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
            $view->warningMessage('Client not found');
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
            $this->checkToken();
            
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
            $view->warningMessage('Client not found');
            self::redirect('/client');
        }
        
        $view->set('client', $client);
        
        if(RequestMethods::post('submitEditClient')){
            $this->checkToken();
            
            $client->contactPerson = RequestMethods::post('contperson');
            $client->contactEmail = RequestMethods::post('contemail');
            $client->companyName = RequestMethods::post('compname');
            $client->companyAddress = RequestMethods::post('compaddress');
            $client->contactPhone = RequestMethods::post('contphone');
            $client->www = RequestMethods::post('www');
            
            if($client->validate()){
                $client->save();
                
                Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                $view->successMessage('All changes were successfully saved');
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
            $view->warningMessage('Client not found');
            self::redirect('/client');
        }

        $view->set('client', $client);

        if (RequestMethods::post('submitDeleteClient')) {
            $this->checkToken();

            if ($client->delete()) {

                Event::fire('app.log', array('success', 'Client id: ' . $client->getId()));
                $view->successMessage('Client has been deleted successfully');
                self::redirect('/client');
            } else {
                Event::fire('app.log', array('fail', 'Client id: ' . $client->getId()));
                $view->errorMessage('An error occured while deleting the client');
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
        
        $view->set('projects', $clientProjects);

        if(RequestMethods::post('submitReportIssue')){
            $this->checkToken();
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));
            
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
            
            if($task->validate()){
                $tid = $task->save();
                
                Event::fire('app.log', array('success', 'Issue id: ' . $tid));
                $view->successMessage('Issue has been successfully reported');
                self::redirect('/');
            }else{
                Event::fire('app.log', array('fail'));
                $view->set('errors', $task->getErrors())
                        ->set('task', $task);
            }
        }
    }

}
