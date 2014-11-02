<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\ArrayMethods;

/**
 * Description of App_Controller_Task
 *
 * @author Tomy
 */
class App_Controller_Task extends Controller
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
    public function add($projectId)
    {
        $view = $this->getActionView();

        $project = App_Model_Project::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int)$projectId));

        if ($project === null) {
            $view->warningMessage('Project not found');
            self::redirect('/project');
        }

        $users = App_Model_User::all(array(
                    'deleted = ?' => false,
                    'active = ?' => true
                        ), array('id', 'firstname', 'lastname'));

        $tasks = App_Model_Task::all(
                        array('active = ?' => true, 'deleted = ?' => false), 
                        array('id', 'title'));

        $view->set('projectid', $project->getId())
                ->set('tasks', $tasks)
                ->set('users', $users)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddTask')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/project');
            }
            $errors = array();
            
            $urlKey = $project->getTaskPrefix().'-'.$project->getNextTaskNumber();

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['urlKey'] = array('This task already exists');
            }
            
            if (RequestMethods::post('type') == 'inquiry' || RequestMethods::post('type') == 'bug') {
                $state = 8;
            } elseif (RequestMethods::post('type', 'task') == 'task') {
                $state = 9;
            }

            $task = new App_Model_Task(array(
                'stateId' => $state,
                'projectId' => RequestMethods::post('projectid'),
                'createdBy' => $this->getUser()->getId(),
                'assignedTo' => RequestMethods::post('assignTo', $this->getUser()->getId()),
                'urlKey' => $urlKey,
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'taskType' => RequestMethods::post('type', 'task'),
                'priority' => RequestMethods::post('priority', 1),
                'spentTimeTotal' => ''
            ));

            if (RequestMethods::post('subtaskof', null)) {
                $parts = explode('|', RequestMethods::post('subtaskof'));
                $checkUrl = App_Model_Task::first(
                                array('active = ?' => true,'deleted = ?' => false,'id = ?' => $parts[1]), 
                                array('id')
                );

                if ($checkUrl === null) {
                    $errors['subTask'] = array('This task doesnt exists');
                }
            }

            if (RequestMethods::post('relatedto', null)) {
                $parts = explode('|', RequestMethods::post('relatedto'));
                $checkUrl = App_Model_Task::first(
                                array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => $parts[1]), 
                                array('id')
                );

                if ($checkUrl === null) {
                    $errors['relatedTask'] = array('This task doesnt exists');
                }
            }

            if (empty($errors) && $task->validate()) {
                $tid = $task->save();

                if (RequestMethods::post('subtaskof', null)) {
                    $parts = explode('|', RequestMethods::post('subtaskof'));
                    
                    $subTask = new App_Model_TaskSubTask(array(
                        'taskId' => (int) $parts[1],
                        'subTaskId' => $tid
                    ));
                    $subTask->save();
                    Event::fire('app.log', array('success', 'Task id: ' . $tid . ' subtask of ' . $parts[1]));
                }

                if (RequestMethods::post('relatedto', null)) {
                    $parts = explode('|', RequestMethods::post('relatedto'));

                    $relTask = new App_Model_TaskRelated(array(
                        'taskId' => $tid,
                        'relatesTo' => (int) $parts[1]
                    ));
                    $relTask->save();
                    Event::fire('app.log', array('success', 'Task id: ' . $tid . ' related to ' . $parts[1]));

                    $relTaskCross = new App_Model_TaskRelated(array(
                        'taskId' => (int) $parts[1],
                        'relatesTo' => $tid
                    ));
                    $relTaskCross->save();
                    Event::fire('app.log', array('success', 'Task id: ' . $parts[1] . ' related to ' . $tid));
                }
                
                $project->nextTaskNumber += 1;
                $project->save();

                Event::fire('app.log', array('success', 'Task id: ' . $tid));
                $view->successMessage('Task has been successfully saved');
                self::redirect('/task/' . $task->getUrlKey() . '/');
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('newtask', $task)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $errors + $task->getErrors());
            }
        }
    }

    /**
     * @before _secured, _developer
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $task = App_Model_Task::fetchTaskByIdBasicInfo($id);

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $users = App_Model_User::all(array(
                    'deleted = ?' => false,
                    'active = ?' => true
        ));

        $states = App_Model_State::all(array(
                    'active = ?' => true,
                    'type = ?' => 'task'
        ));

        $view->set('task', $task)
                ->set('states', $states)
                ->set('users', $users);

        if (RequestMethods::post('submitEditTask')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }

            $task->stateId = RequestMethods::post('state');
            $task->active = RequestMethods::post('active');
            $task->assignedTo = RequestMethods::post('assignTo', $this->getUser()->getId());
            $task->title = RequestMethods::post('title');
            $task->description = RequestMethods::post('description');
            $task->priority = RequestMethods::post('priority', 1);
            $task->spentTimeTotal = RequestMethods::post('ttr');

            if ($task->validate()) {
                $task->save();

                Event::fire('app.log', array('success', 'Task id: ' . $task->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/task/' . $task->getUrlKey() . '/');
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
                $view->set('task', $task)
                        ->set('errors', $task->getErrors());
            }
        }
    }

    /**
     * @before _secured, _client
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $task = App_Model_Task::fetchTaskById($id);

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $taskTime = App_Model_TaskTime::all(
                        array('taskId = ?' => $task->getId()), 
                        array('spentTime'));

        $spentTime = 0;
        foreach ($taskTime as $time) {
            $spentTime += $time->getSpentTime();
        }

        $nextStates = App_Model_State::all(
                        array(
                            'active = ?' => true,
                            'type = ?' => 'task',
                            'previousState = ?' => $task->getStateId()
        ));

        $view->set('task', $task)
                ->set('spenttime', $spentTime)
                ->set('nextstates', $nextStates);

        if (RequestMethods::post('submitSendMess')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            
            $chatMessage = new App_Model_TaskChat(array(
                'taskId' => $task->getId(),
                'userId' => $this->getUser()->getId(),
                'isPublic' => RequestMethods::post('showto', 1),
                'title' => RequestMethods::post('messtitle'),
                'body' => RequestMethods::post('messtext')
            ));

            if ($chatMessage->validate()) {
                $messId = $chatMessage->save();

                Event::fire('app.log', array('success', 'Message id: ' . $messId));
                $view->successMessage('Message has been successfully saved');
                self::redirect('/task/' . $task->getUrlKey() . '/#chat');
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
                $view->set('message', $chatMessage)
                        ->set('errors', $chatMessage->getErrors());
            }
        }
    }

    /**
     * @before _secured, _developer
     */
    public function assignToUser($taskId)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $taskId));

        $users = App_Model_User::all(
                        array('active = ?' => true, 'deleted = ?' => false), 
                        array('id', 'firstname', 'lastname'));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }

        if ($users === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }

        $view->set('taskid', $task->getId())
                ->set('users', $users);

        if (RequestMethods::post('assigntouser')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            
            $task->assignedTo = RequestMethods::post('user', $this->getUser()->getId());

            if ($task->validate()) {
                $task->save();

                Event::fire('app.log', array('success', 'Task id: ' . $task->getId() . ' - Assign to: ' . $task->assignedTo));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/task/' . $task->getUrlKey() . '/');
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/project/detail/' . $task->getProjectId());
            }
        }
    }

    /**
     * @before _secured, _developer
     */
    public function assignToMe($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if ($task === null) {
            $view->warningMessage('Task not found');
            self::redirect('/');
        }

        $task->assignedTo = $this->getUser()->getId();
        if ($task->validate()) {
            $task->save();

            Event::fire('app.log', array('success', 'Task id: ' . $task->getId()));
            $view->successMessage(self::SUCCESS_MESSAGE_2);
            self::redirect('/task/' . $task->getUrlKey() . '/');
        } else {
            Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
            $view->errorMessage(self::ERROR_MESSAGE_1);
            self::redirect('/project/detail/' . $task->getProjectId());
        }
    }

    /**
     * @before _secured, _developer
     * @param type $id
     */
    public function setTaskState($taskId, $state)
    {
        $view = $this->getActionView();

        $task = App_Model_Task::first(array('id = ?' => (int) $taskId));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        if ($state == 15) {
            $taskTime = App_Model_TaskTime::all(
                            array('taskId = ?' => $task->getId()), 
                            array('spentTime'));

            $spentTime = 0;
            foreach ($taskTime as $time) {
                $spentTime += $time->getSpentTime();
            }

            $task->spentTimeTotal = $spentTime;
        }

        $task->stateId = (int) $state;

        if ($task->validate()) {
            $task->save();

            Event::fire('app.log', array('success', 'Task id: ' . $task->getId() . ' - new state: ' . $task->stateId));
            $view->successMessage(self::SUCCESS_MESSAGE_2);
            self::redirect('/task/' . $task->getUrlKey() . '/');
        } else {
            Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
            $view->warningMessage(self::ERROR_MESSAGE_1);
            self::redirect('/task/' . $task->getUrlKey() . '/');
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('deleted = ?' => false, 'id = ?' => (int) $id));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }

        $view->set('task', $task);

        if (RequestMethods::post('submitDeleteTask')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }
            $task->deleted = true;

            if ($task->validate()) {
                $task->save();

                Event::fire('app.log', array('success', 'Task id: ' . $task->getId()));
                $view->successMessage('Task has been deleted successfully');
                self::redirect('/project/detail/' . $task->getProjectId());
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/project/detail/' . $task->getProjectId());
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
            $task = App_Model_Task::first(array('id = ?' => (int) $id));

            if ($task === null) {
                echo self::ERROR_MESSAGE_2;
            }

            $task->deleted = false;

            if ($task->validate()) {
                $task->save();

                Event::fire('app.log', array('success', 'Task id: ' . $task->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $task->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _client
     */
    public function uploadAttachment($id)
    {
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $view->set('taskid', $task->getId())
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

            $fileErrors = $fileManager->upload('files', 'pr-' . $task->getProjectId().'/tk-'.$task->getId(), time().'_')->getUploadErrors();
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
                            'taskId' => $task->getId(),
                            'attachmentId' => $aid
                        ));
                        $prAttch->save();

                        Event::fire('app.log', array('success', 'Attachment id: ' . $aid . 'for task '.$task->getId().' in project ' . $task->getProjectId()));
                    } else {
                        $errors['attachment'][] = $attachment->getErrors();
                    }
                }
            }

            if (empty($errors) && empty($fileErrors)) {
                $view->successMessage('Attachment has been successfully saved');
                self::redirect('/task/' . $task->getUrlKey() . '/#files');
            } else {
                $errors['files'] = $fileErrors;
                Event::fire('app.log', array('fail', 'Attachment for task in project ' . $task->getId()));
                $view->set('errors', $errors)
                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _developer
     * @param type $taskId
     * @param type $subTaskId
     */
    public function removeSubTask($taskId, $subTaskId)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        if ($this->checkCSRFToken()) {
            $subTask = App_Model_TaskSubTask::first(array(
                        'taskId = ?' => (int) $taskId,
                        'subTaskId = ?' => (int) $subTaskId
            ));

            if ($subTask === null) {
                echo self::ERROR_MESSAGE_2;
            }

            if ($subTask->delete()) {
                Event::fire('app.log', array('success', 'Task id: ' . $taskId . ' - subtask id: ' . $subTaskId));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $taskId . ' - subtask id: ' . $subTaskId));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _developer
     * @param type $taskId
     * @param type $relTaskId
     */
    public function removeReletedTask($taskId, $relTaskId)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        if ($this->checkCSRFToken()) {
            $relTask1 = App_Model_TaskSubTask::first(array(
                        'taskId = ?' => (int) $taskId,
                        'subTaskId = ?' => (int) $relTaskId
            ));

            $relTask2 = App_Model_TaskSubTask::first(array(
                        'taskId = ?' => (int) $relTaskId,
                        'subTaskId = ?' => (int) $taskId
            ));

            if ($relTask1 === null || $relTask2 === null) {
                echo self::ERROR_MESSAGE_2;
            }

            if ($relTask1->delete() && $relTask2->delete()) {
                Event::fire('app.log', array('success', 'Task id: ' . $taskId . ' - related task id: ' . $relTaskId));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Task id: ' . $taskId . ' - related task id: ' . $relTaskId));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _developer
     * @param type $id
     */
    public function addSubTask($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if ($task === null) {
            $view->warningMessage('Task not found');
            self::redirect('/');
        }

        $allTasks = App_Model_Task::all(
                        array('active = ?' => true,
                            'deleted = ?' => false,
                            'urlKey <> ?' => $task->getUrlKey())
        );

        $view->set('tasks', $allTasks)
                ->set('taskid', $task->getId());

        if (RequestMethods::post('submitAddSubtask')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }

            $subtask = new App_Model_TaskSubTask(array(
                'taskId' => $task->getId(),
                'subTaskId' => RequestMethods::post('subtask')
            ));
            $subid = $subtask->save();

            Event::fire('app.log', array('success', 'Subtask id: ' . $subid . ' for task ' . $task->getId()));
            $view->successMessage('Subtask has been successfully saved');
            self::redirect('/task/' . $task->getUrlKey() . '/');
        }
    }

    /**
     * @before _secured, _developer
     * @param type $id
     */
    public function addRelatedTask($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }

        $allTasks = App_Model_Task::all(
                        array('active = ?' => true,
                            'deleted = ?' => false,
                            'urlKey <> ?' => $task->getUrlKey())
        );

        $view->set('tasks', $allTasks)
                ->set('taskid', $task->getId());

        if (RequestMethods::post('submitAddReltask')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }

            $reltask1 = new App_Model_TaskRelated(array(
                'taskId' => $task->getId(),
                'relatesTo' => RequestMethods::post('reltask')
            ));
            $reltask1->save();


            $reltask2 = new App_Model_TaskRelated(array(
                'taskId' => RequestMethods::post('reltask'),
                'relatesTo' => $task->getId()
            ));
            $reltask2->save();

            Event::fire('app.log', array('success', 'Related task id: ' . $reltask2->getTaskId() . ' to task ' . $task->getId()));
            $view->successMessage('Related task has been successfully saved');
            self::redirect('/task/' . $task->getUrlKey() . '/');
        }
    }

    /**
     * @before _secured, _developer
     * @param type $id
     */
    public function logTime($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $task = App_Model_Task::first(
                    array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id), 
                    array('id', 'urlKey'));

        if ($task === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
        }

        $view->set('task', $task);

        if (RequestMethods::post('submitLogTime')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/project');
            }

            $taskTimeExist = App_Model_TaskTime::first(
                            array(
                                'taskId = ?' => $task->getId(),
                                'userId = ?' => $this->getUser()->getId(),
                                'logDate = ?' => RequestMethods::post('date', date('Y-m-d', time()))
            ));

            if ($taskTimeExist === null) {
                $taskTime = new App_Model_TaskTime(array(
                    'taskId' => $task->getId(),
                    'userId' => $this->getUser()->getId(),
                    'spentTime' => RequestMethods::post('time'),
                    'description' => RequestMethods::post('description'),
                    'logDate' => RequestMethods::post('date', date('Y-m-d', time()))
                ));

                if ($taskTime->validate()) {
                    $ttid = $taskTime->save();

                    Event::fire('app.log', array('success', 'Time log ' . $ttid . ' for task ' . $task->getId()));
                    $view->successMessage('Time has been successfully loged');
                    self::redirect('/task/' . $task->getUrlKey() . '/');
                } else {
                    Event::fire('app.log', array('fail', 'Time log for task ' . $task->getId()));
                    $view->set('tasktime', $taskTime)
                            ->set('errors', $taskTime->getErrors());
                }
            } else {
                $taskTimeExist->spentTime = $taskTimeExist->spentTime + RequestMethods::post('time');
                $taskTimeExist->save();
                
                Event::fire('app.log', array('success', 'Time log ' . $taskTimeExist->getId() . ' for task ' . $task->getId()));
                $view->successMessage('Time has been successfully loged');
                self::redirect('/task/' . $task->getUrlKey() . '/');
            }
        }
    }

    /**
     * @before _secured, _developer
     * @param type $id
     */
    public function deleteTimeLog($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $timelog = App_Model_TaskTime::first(
                            array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));

            if ($timelog === null) {
                echo self::ERROR_MESSAGE_2;
            }

            if ($timelog->delete()) {
                Event::fire('app.log', array('success', 'Time log for task: ' . $timelog->getTaskId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Time log for task: ' . $timelog->getTaskId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
