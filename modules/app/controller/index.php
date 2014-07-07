<?php

use App\Etc\Controller;

class App_Controller_Index extends Controller
{

    /**
     * @before _secured
     */
    public function index()
    {
        $view = $this->getActionView();
        $userId = $this->getUser()->getId();
        
        if($this->isProjectManager()){
            $assignedTo = App_Model_User::fetchAssignedToProjects($userId);
            $currentTasks = App_Model_User::fetchAssignedToTasks($userId);
            $managedProjects = App_Model_User::fetchManagedProjects($userId);
            $deadlines = App_Model_Project::fetchDeadlineProjects();
            $notes = App_Model_Note::all(array('active = ?' => true, 'userId = ?' => $userId));

            try{
                $projectsWaitingForApproval = App_Model_Project::fetchProjectsByState('Waiting for approval');
                $tasksWaitingForApproval = App_Model_Task::fetchTasksByState('Waiting for approval');
            }  catch (Exception $e){
                $view->errorMessage($e->getMessage());
            }

            $view->set('assignedTo', $assignedTo)
                    ->set('currentTasks', $currentTasks)
                    ->set('managedProjects', $managedProjects)
                    ->set('deadlines', $deadlines)
                    ->set('notes', $notes)
                    ->set('tasksWaitingToApprove', $tasksWaitingForApproval)
                    ->set('projectsWaitingToApprove', $projectsWaitingForApproval);
        }elseif($this->isDeveloper()){
            $assignedTo = App_Model_User::fetchAssignedToProjects($userId);
            $currentTasks = App_Model_User::fetchAssignedToTasks($userId);
            $deadlines = App_Model_Project::fetchDeadlineProjects();
            $notes = App_Model_Note::all(array('active = ?' => true, 'userId = ?' => $userId));
            
            $view->set('assignedTo', $assignedTo)
                    ->set('currentTasks', $currentTasks)
                    ->set('notes', $notes)
                    ->set('deadlines', $deadlines);
        }elseif($this->isClient()){
            $clientProjects = App_Model_Project::fetchProjectsByClientId($this->getUser()->getClientId());
            
            $view->set('clientProjects', $clientProjects);
        }
    }

}
