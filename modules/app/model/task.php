<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Task
 *
 * @author Tomy
 */
class App_Model_Task extends Model
{
    /**
     * @readwrite
     */
    protected $_alias = 'tk';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_projectId;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_stateId;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_createdBy;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     */
    protected $_assignedTo;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     * @label active
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * @index
     * @unique
     *
     * @validate required, alphanumeric, max(100)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     *
     * @validate required, alphanumeric, max(150)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate required, html, max(4096)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     *
     * @validate required, alphanumeric, max(50)
     * @label type
     */
    protected $_taskType;

    /**
     * @column
     * @readwrite
     * @type tinyint
     *
     * @validate required, numeric, max(2)
     * @label priority
     */
    protected $_priority;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     *
     * @validate alphanumeric, max(25)
     * @label spent time total
     */
    protected $_spentTimeTotal;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     */
    protected $_deleted;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * @readwrite
     * @var type 
     */
    protected $_attachment;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_chat;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_subTasks;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_relatedTo;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_timeLog;
    
    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
            $this->setDeleted(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $stateVal
     * @return string
     */
    public static function fetchTasksByState($stateVal)
    {
        if(is_numeric($stateVal)){
            $state = App_Model_State::first(array('id = ?' => $stateVal, 'type = ?' => 'task'));
        }elseif(is_string($stateVal)){
            $state = App_Model_State::first(array('title = ?' => $stateVal, 'type = ?' => 'task'));
        }else{
            throw new \Exception('Param has to be state name or id');
        }
        
        if($state === null){
            throw new \Exception('Task state not found');
        }else{
            $stateId = $state->getId();
            $task = new self(array('stateId'=> $stateId));
            return $task->getTasksByState();
        }
    }
    
    /**
     * 
     * @param type $key
     */
    public static function fetchTaskByUrlKey($key)
    {
        return self::first(array('urlKey = ?' => $key));
    }
    
    /**
     * 
     * @param type $type
     * @return type
     */
    public static function fetchTaskByTypeByProject($type, $projectId)
    {
        $task = new self(array('taskType' => $type, 'projectId' => (int) $projectId));
        return $task->getTaskByType();
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchTaskByIdBasicInfo($id)
    {
        $taskQuery = self::getQuery(array('tk.*'))
                ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                        array('pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                        array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                ->join('tb_user', 'uss.id = tk.assignedTo', 'uss', 
                        array('uss.firstname' => 'asFname', 'uss.lastname' => 'asLname'))
                ->where('tk.id = ?', (int) $id)
                ->where('tk.deleted = ?', false);
        $taskArr = self::initialize($taskQuery);
        $task = array_shift($taskArr);

        return $task;
    }

    /**
     * 
     * @return type
     */
    public static function fetchDeletedTasks()
    {
         $taskQuery = self::getQuery(array('tk.*'))
                ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                        array('pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                        array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                ->join('tb_user', 'uss.id = tk.assignedTo', 'uss', 
                        array('uss.firstname' => 'asFname', 'uss.lastname' => 'asLname'))
                ->where('tk.deleted = ?', true);
        $tasks = self::initialize($taskQuery);

        return $tasks;
    }
    /**
     * 
     * @param type $id
     */
    public static function fetchTaskById($id)
    {
        if(is_numeric($id)){
            $taskQuery = self::getQuery(array('tk.*'))
                    ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                    ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                            array('pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                    ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                            array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                    ->join('tb_user', 'uss.id = tk.assignedTo', 'uss', 
                            array('uss.firstname' => 'asFname', 'uss.lastname' => 'asLname'))
                    ->where('tk.id = ?', (int)$id)
                    ->where('tk.deleted = ?', false);
            $taskArr = self::initialize($taskQuery);
            $task = array_shift($taskArr);
        }elseif(is_string($id)){
            $taskQuery = self::getQuery(array('tk.*'))
                    ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                    ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                            array('pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                    ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                            array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                    ->join('tb_user', 'uss.id = tk.assignedTo', 'uss', 
                            array('uss.firstname' => 'asFname', 'uss.lastname' => 'asLname'))
                    ->where('tk.urlKey = ?', (string)$id)
                    ->where('tk.deleted = ?', false);
            $taskArr = self::initialize($taskQuery);
            $task = array_shift($taskArr);
        }
        
        if($task === null){
            return null;
        }else{
            return $task->getTaskById();
        }
    }

    /**
     * 
     * @return type
     */
    public function getTaskByType()
    {
        $taskQuery = self::getQuery(array('tk.*'))
                ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                        array('pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                        array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                ->join('tb_user', 'uss.id = tk.assignedTo', 'uss', 
                        array('uss.firstname' => 'asFname', 'uss.lastname' => 'asLname'))
                ->where('tk.taskType = ?', (string) $this->getTaskType())
                ->where('tk.projectId = ?', $this->getProjectId())
                ->where('tk.active = ?', true)
                ->where('tk.deleted = ?', false);
        $tasks = self::initialize($taskQuery);

        return $tasks;
    }
            
    /**
     * 
     * @return \App_Model_Task
     */
    public function getTaskById()
    {
        $chatQuery = App_Model_TaskChat::getQuery(array('tc.*'))
                ->join('tb_user', 'tc.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->where('tc.taskId = ?', $this->getId());
        $this->_chat = App_Model_TaskChat::initialize($chatQuery);
        
        $subTaskQuery = App_Model_TaskSubTask::getQuery(array('ts.*'))
                ->join('tb_task', 'ts.subTaskId = tk.id', 'tk', 
                        array('tk.*'))
                ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.title' => 'stateTitle'))
                ->where('ts.taskId = ?', $this->getId());
        $this->_subTasks = App_Model_TaskSubTask::initialize($subTaskQuery);
        
        $relatedTasksQuery = App_Model_TaskRelated::getQuery(array('tr.*'))
                ->join('tb_task', 'tr.relatesTo = tk.id', 'tk', 
                        array('tk.*'))
                ->join('tb_state', 'tk.stateId = s.id', 's', 
                        array('s.title' => 'stateTitle'))
                ->where('tr.taskId = ?', $this->getId());
        $this->_relatedTo = App_Model_TaskRelated::initialize($relatedTasksQuery);
        
        $taskTimeQuery = App_Model_TaskTime::getQuery(array('tt.*'))
                ->join('tb_task', 'tt.taskId = tk.id', 'tk', 
                        array('tk.id' => 'tkId'))
                ->join('tb_user', 'tt.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->where('tt.taskId = ?', $this->getId());
        $this->_timeLog = App_Model_TaskTime::initialize($taskTimeQuery);
        
        $taskAttachQuery = App_Model_TaskAttachment::getQuery(array('ta.*'))
                ->join('tb_attachment', 'ta.attachmentId = tk.id', 'tk', 
                        array('tk.*'))
                ->where('ta.taskId = ?', $this->getId());
        $this->_attachment = App_Model_TaskAttachment::initialize($taskAttachQuery);
        
        return $this;
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public function getTasksByState()
    {
        $taskQuery = self::getQuery(array('tk.title', 'tk.urlKey', 'tk.created', 'tk.taskType'))
                ->join('tb_project', 'tk.projectId = pr.id', 'pr', 
                        array('pr.title' => 'pTitle', 'pr.urlKey' => 'pUrlKey'))
                ->join('tb_user', 'us.id = tk.createdBy', 'us', 
                        array('us.firstname' => 'tkFname', 'us.lastname' => 'tkLname'))
                ->where('tk.stateId = ?', (int)$this->getStateId())
                ->where('tk.active = ?', true)
                ->where('tk.deleted = ?', false);
        $tasks = self::initialize($taskQuery);
            
        return $tasks;
    }
}
