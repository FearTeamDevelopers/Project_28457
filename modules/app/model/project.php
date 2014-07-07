<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Project
 *
 * @author Tomy
 */
class App_Model_Project extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pr';
    
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
    protected $_managerId;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_clientId;

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
     * @type tinyint
     *
     * @validate required, numeric, max(2)
     * @label priority
     */
    protected $_priority;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate required, numeric
     * @label budget
     */
    protected $_maxBudget;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     * @label paid
     */
    protected $_isPaid;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     *
     * @validate date, max(20)
     * @label planned start
     */
    protected $_plannedStart;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     *
     * @validate date, max(20)
     * @label planned end
     */
    protected $_plannedEnd;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     *
     * @validate url, max(255)
     * @label git repository
     */
    protected $_gitRepository;
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
    protected $_budget;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_budgetTotal;

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
    protected $_progress;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_assignedUsers;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_tasks;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_bugs;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_inquiries;
    
    /**
     * 
     */
    protected function loadProgress()
    {
        $done = App_Model_Task::count(array(
            'stateId = ?' => 15,
        ));
        $all = App_Model_Task::count(array(
            'stateId IN ?' => array(9,10,11,12,13,14,15)
        ));

        if ($all == 0 && $done == 0) {
            $progress = 0;
        } else {
            $progress = $done / $all;
        }

        if ($progress != 0 && $this->getStateId() == 2) {
            $this->stateId = 4;
            $this->save();
        } elseif ($progress == 1) {
            $this->stateId = 7;
            $this->save();
        }
        
        return $progress;
    }
    
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
            $this->setIsPaid(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $stateVal
     * @return string
     */
    public static function fetchProjectsByState($stateVal)
    {
        if(is_numeric($stateVal)){
            $state = App_Model_State::first(array('id = ?' => $stateVal, 'type = ?' => 'project'));
        }elseif(is_string($stateVal)){
            $state = App_Model_State::first(array('title = ?' => $stateVal, 'type = ?' => 'project'));
        }else{
            throw new \Exception('Param has to be state name or id');
        }
        
        if($state === null){
            throw new \Exception('Project state not found');
        }else{
            $stateId = $state->getId();
            $project = new self(array('stateId'=> $stateId));
            return $project->getProjectsByState();
        }
    }
    
    /**
     * 
     * @return type
     */
    public static function fetchDeletedProjects()
    {
        $projectQuery = self::getQuery(array('pr.*'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                    array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                ->join('tb_user', 'us.id = pr.managerId', 'us', 
                        array('us.firstname' => 'mFname', 'us.lastname' => 'mLname'))
                ->where('pr.deleted = ?', true);
        return self::initialize($projectQuery);
    }


    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchProjectById($id)
    {
        if(is_numeric($id)){
            $projectQuery = self::getQuery(array('pr.*'))
                    ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                    ->join('tb_client', 'pr.clientId = c.id', 'c', 
                            array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                    ->join('tb_user', 'us.id = pr.managerId', 'us', 
                            array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                    ->where('pr.id = ?', (int)$id)
                    ->where('pr.active = ?', true)
                    ->where('pr.deleted = ?', false);
            $projectArr = self::initialize($projectQuery);
            $project = array_shift($projectArr);
        }elseif(is_string($id)){
            $projectQuery = self::getQuery(array('pr.*'))
                    ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                    ->join('tb_client', 'pr.clientId = c.id', 'c', 
                            array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                    ->join('tb_user', 'us.id = pr.managerId', 'us', 
                            array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                    ->where('pr.urlKey = ?', (string)$id)
                    ->where('pr.active = ?', true)
                    ->where('pr.deleted = ?', false);
            $projectArr = self::initialize($projectQuery);
            $project = array_shift($projectArr);
        }
        
        if($project === null){
            return null;
        }else{
            return $project->getProjectById();
        }
    }
    
    /**
     * 
     */
    public static function fetchProjectsWithBasicInfo()
    {
        $projectsQuery = self::getQuery(array('pr.*'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                ->join('tb_user', 'us.id = pr.managerId', 'us', 
                        array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                ->where('pr.active = ?', true)
                ->where('pr.deleted = ?', false);
        $projects = self::initialize($projectsQuery);
        
        return $projects;
    }
    
    /**
     * 
     * @param type $state
     * @param type $priority
     */
    public static function fetchProjectsWithBasicInfoByFilter($state, $priority)
    {
        $projectsQuery = self::getQuery(array('pr.*'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                ->join('tb_user', 'us.id = pr.managerId', 'us', 
                        array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                ->where('pr.deleted = ?', false);
        
        if(!empty($state)){
            $projectsQuery->where('pr.stateId IN ?', (array) $state);
        }
        
        if(!empty($priority)){
            $projectsQuery->where('pr.priority IN ?', (array) $priority);
        }
        
        $projectsQuery->order('pr.created', 'desc');
        $projects = self::initialize($projectsQuery);
        
        return $projects;
    }
    
    /**
     * 
     * @return array
     */
    public static function fetchDeadlineProjects()
    {
        $projectsQuery = self::getQuery(array('pr.*'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                ->where('datediff(DATE(CURDATE()), DATE(pr.plannedEnd)) between -7 and 0', '')
                ->where('pr.active = ?', true)
                ->where('pr.deleted = ?', false)
                ->order('pr.created', 'desc');
        $projects = self::initialize($projectsQuery);
        
        return $projects;
    }
    
    /**
     * 
     * @param type $id
     */
    public static function fetchProjectsByClientId($id)
    {
        $projects = new self(array('clientId'=> (int)$id));
        return $projects->getProjectsByClientId();
    }

    /**
     * 
     * @param type $key
     */
    public static function fetchProjectByUrlKey($key)
    {
        return self::first(array('urlKey = ?' => $key));
    }
    
    /**
     * 
     * @return type
     */
    public function getProjectsByClientId()
    {
        $projectsQuery = self::getQuery(array('pr.*'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_user', 'us.id = pr.managerId', 'us', 
                        array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                ->where('pr.clientId', $this->getClientId())
                ->where('pr.active = ?', true)
                ->where('pr.deleted = ?', false)
                ->order('pr.created', 'desc');
        $projects = self::initialize($projectsQuery);
        
        return $projects;
    }
    /**
     * 
     * @param type $id
     * @return type
     */
    public function getProjectsByState()
    {
        $projectQuery = self::getQuery(array('pr.title', 'pr.urlKey', 'pr.created'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.id' => 'cId', 'c.contactPerson'))
                ->join('tb_user', 'us.id = pr.managerId', 'us', 
                        array('us.firstname' => 'managerFname', 'us.lastname' => 'managerLname'))
                ->where('pr.stateId = ?', (int)$this->getStateId())
                ->where('pr.active = ?', true)
                ->where('pr.deleted = ?', false)
                ->order('pr.created', 'desc');
        $projects = self::initialize($projectQuery);
            
        return $projects;
    }

    /**
     * 
     * @return \App_Model_Project
     */
    public function getProjectById()
    {
        $budgetQuery = App_Model_ProjectBudget::getQuery(array('pb.*'))
                ->join('tb_user', 'pb.userId = us.id', 'us', 
                        array('us.firstname' => 'fname', 'us.lastname' => 'lname'))
                ->join('tb_project', 'pb.projectId = pr.id', 'pr', 
                        array('pr.title' => 'pTitle', 'pr.urlKey' => 'pUrlKey'))
                ->where('pb.projectId = ?', $this->getId())
                ->where('pb.active = ?', true)
                ->where('pb.deleted = ?', false)
                ->order('pb.created', 'desc');
        $this->_budget = App_Model_ProjectBudget::initialize($budgetQuery);
        $this->_budgetTotal = App_Model_ProjectBudget::countProjectBudget($this->getId());
        
        $chatQuery = App_Model_ProjectChat::getQuery(array('pc.*'))
                ->join('tb_user', 'pc.userId = us.id', 'us', 
                        array('us.firstname' => 'fname', 'us.lastname' => 'lname'))
                ->join('tb_project', 'pc.projectId = pr.id', 'pr', 
                        array('pr.title' => 'pTitle', 'pr.urlKey' => 'pUrlKey'))
                ->where('pc.projectId = ?', $this->getId())
                ->where('pc.active = ?', true)
                ->order('pc.created', 'desc');
        $this->_chat = App_Model_ProjectChat::initialize($chatQuery);
        
        $attachmentQuery = App_Model_Attachment::getQuery(array('at.*'))
                ->join('tb_user', 'at.userId = us.id', 'us', 
                        array('us.firstname' => 'aFname', 'us.lastname' => 'aLname'))
                ->join('tb_projectattachment', 'at.id = pa.attachmentId', 'pa', 
                        array('pa.projectId' => 'paProjectId', 'pa.attachmentId' => 'paAttachId'))
                ->join('tb_project', 'pa.projectId = pr.id', 'pr', 
                        array('pr.title' => 'pTitle', 'pr.urlKey' => 'pUrlKey'))
                ->where('pa.projectId = ?', $this->getId())
                ->where('at.active = ?', true)
                ->order('at.created', 'desc');
        $this->_attachment = App_Model_Attachment::initialize($attachmentQuery);
        
        $assigUsersQuery = App_Model_ProjectUser::getQuery(array('pu.*'))
                ->join('tb_user', 'pu.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->where('pu.projectId = ?', $this->getId());
        $this->_assignedUsers = App_Model_ProjectUser::initialize($assigUsersQuery);
        
        $this->_tasks = App_Model_Task::fetchTaskByTypeByProject('task', $this->getId());
        $this->_bugs = App_Model_Task::fetchTaskByTypeByProject('bug', $this->getId());
        $this->_inquiries = App_Model_Task::fetchTaskByTypeByProject('inquiry', $this->getId());
        
        $this->_progress = $this->loadProgress();
        
        return $this;
    }

}
