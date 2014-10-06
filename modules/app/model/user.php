<?php

use THCFrame\Model\Model;
use THCFrame\Security\UserInterface;

/**
 * Description of App_Model_User
 *
 * @author Tomy
 */
class App_Model_User extends Model implements UserInterface
{

    /**
     * @readwrite
     */
    protected $_alias = 'us';

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
     * @validate numeric, max(8)
     */
    protected $_clientId;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * @index
     * @unique
     *
     * @validate required, email, max(60)
     * @label email address
     */
    protected $_email;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * @index
     *
     * @validate required, min(5), max(250)
     * @label password
     */
    protected $_password;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     * @unique
     *
     * @validate min(30), max(40)
     */
    protected $_salt;

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
     * @length 30
     *
     * @validate alphanumeric, max(30)
     * @label username
     */
    protected $_username;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     * 
     * @validate required, alpha, max(25)
     * @label user role
     */
    protected $_role;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     *
     * @validate required, alphanumeric, max(40)
     * @label first name
     */
    protected $_firstname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     *
     * @validate required, alphanumeric, max(40)
     * @label last name
     */
    protected $_lastname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     *
     * @validate numeric, max(25)
     * @label phone
     */
    protected $_phone;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     *
     * @validate datetime, max(25)
     * @label password expiration
     */
    protected $_pwdExpire;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     *
     * @validate alphanumeric, max(50)
     * @label password reset key
     */
    protected $_pwdResetKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     * 
     * @validate datetime, max(25)
     * @label password reset key expiration
     */
    protected $_pwdResetKeyExpire;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     */
    protected $_projectStateFilter;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     */
    protected $_taskStateFilter;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     */
    protected $_projectPriorityFilter;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     */
    protected $_taskPriorityFilter;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_lastLogin;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate numeric, max(30)
     * @label account lockdown time
     */
    protected $_loginLockdownTime;

    /**
     * @column
     * @readwrite
     * @type tinyint
     *
     * @validate numeric, max(2)
     * @label login attemp counter
     */
    protected $_loginAttempCounter;
    
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
     * @param type $value
     * @throws \THCFrame\Security\Exception\Role
     */
    public function setRole($value)
    {
        $role = strtolower(substr($value, 0, 5));
        if ($role != 'role_') {
            throw new \THCFrame\Security\Exception\Role(sprintf('Role %s is not valid', $value));
        } else {
            $this->_role = $value;
        }
    }

    /**
     * 
     * @return type
     */
    public function getRoleFormated()
    {
        return ucfirst(str_replace('role_', '', $this->_role));
    }

    /**
     * 
     */
    public function isActive()
    {
        return (boolean) $this->_active;
    }

    /**
     * 
     * @return type
     */
    public function getWholeName()
    {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        $str = "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
        return $str;
    }

    /**
     * 
     * @return type
     */
    public function unserializeProjectStateFilter()
    {
        return unserialize($this->_projectStateFilter);
    }

    /**
     * 
     * @return type
     */
    public function unserializeProjectPriorityFilter()
    {
        return unserialize($this->_projectPriorityFilter);
    }

    /**
     * 
     * @return type
     */
    public function unserializeTaskStateFilter()
    {
        return unserialize($this->_taskStateFilter);
    }

    /**
     * 
     * @return type
     */
    public function unserializeTaskPriorityFilter()
    {
        return unserialize($this->_taskPriorityFilter);
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchAssignedToProjects($id)
    {
        $user = new self(array('id' => $id));
        return $user->getAssignedToProjects();
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchAssignedToTasks($id)
    {
        $user = new self(array('id' => $id));
        return $user->getCurrentTasks('all');
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchCurrentTasksPM($id)
    {
        $user = new self(array('id' => $id));
        return $user->getCurrentTasks('pm');
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchCurrentTasksClient($id)
    {
        $user = new self(array('id' => $id));
        return $user->getCurrentTasks('client');
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchCurrentTasksDev($id)
    {
        $user = new self(array('id' => $id));
        return $user->getCurrentTasks('dev');
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchTimeLog($id, $month = null)
    {
        $user = new self(array('id' => $id));
        return $user->getTimeLog($month);
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchManagedProjects($id)
    {
        $user = new self(array('id' => $id));
        return $user->getManagedProjects();
    }

    /**
     * 
     * @return type
     */
    public static function fetchManagers()
    {
        return self::all(array(
                    'active = ?' => true,
                    'deleted = ?' => false,
                    'role IN ?' => array('role_projectmanager', 'role_admin', 'role_superadmin')
        ));
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchUserById($id)
    {
        $query = self::getQuery(array('us.*'))
                ->join('tb_client', 'us.clientId = cl.id', 'cl',
                        array('cl.contactPerson', 'cl.contactEmail', 'cl.companyName'))
                ->where('us.id = ?', (int) $id)
                ->where('us.deleted = ?', false);
        $userArr = self::initialize($query);

        if($userArr !== null){
            return array_shift($userArr);
        }else{
            return null;
        }
        
    }

    /**
     * 
     */
    public function getManagedProjects()
    {
        $query = App_Model_Project::getQuery(
                        array('pr.id' => 'pId', 'pr.title' => 'pTitle', 'pr.urlKey' => 'pUrlKey',
                            'pr.priority' => 'pPriority'))
                ->join('tb_state', 'pr.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'pr.clientId = c.id', 'c', 
                        array('c.companyName', 'c.contactEmail', 'c.contactPerson'))
                ->where('pr.managerId = ?', $this->getId())
                ->where('pr.deleted = ?', false)
                ->order('pr.created', 'desc');

        $result = App_Model_Project::initialize($query);
        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getCurrentTasks($role = 'dev')
    {
        $query = App_Model_Task::getQuery(array('tk.*'))
                ->join('tb_project', 'tk.projectId = p.id', 'p', 
                        array('p.id' => 'pId', 'p.title' => 'pTitle', 'p.urlKey' => 'pUrlKey'))
                ->join('tb_state', 'tk.stateId = s.id', 's',
                        array('s.id' => 'sId', 's.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_user', 'us.id = tk.createdBy', 'us',
                        array('us.firstname' => 'cFname', 'us.lastname' => 'cLname'))
                ->where('tk.assignedTo = ?', $this->getId())
                ->where('tk.deleted = ?', false)
                ->order('p.title', 'ASC')
                ->order('s.id', 'ASC')
                ->order('tk.priority', 'DESC');

        if ($role === 'dev') {
            $query->where('s.id IN ?', array(9, 12, 13));
        } elseif ($role === 'pm') {
            $query->where('s.id IN ?', array(8, 9, 11, 12, 13));
        } elseif ($role === 'client') {
            $query->where('s.id IN ?', array(14));
        } elseif ($role === 'all') {
            $query->where('s.id IN ?', array(8, 9, 10, 11, 12, 13, 14, 15));
        }

        $result = self::initialize($query);
        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getTimeLog($month = null)
    {
        if ($month === null) {
            $month = date('m');
        }

        $firstDayUTS = mktime(0, 0, 0, $month, 1, date('Y'));
        $lastDayUTS = mktime(0, 0, 0, $month, date('t'), date('Y'));

        $firstDay = date('Y-m-d', $firstDayUTS);
        $lastDay = date('Y-m-d', $lastDayUTS);

        $projectQuery = App_Model_TaskTime::getQuery(array('tt.id'))
                ->join('tb_task', 'tt.taskId = tk.id', 'tk',
                        array('tk.id' => 'tId'))
                ->join('tb_project', 'tk.projectId = pr.id', 'pr',
                        array('pr.id' => 'pid', 'pr.title' => 'prTitle', 'pr.urlKey' => 'prUrlKey'))
                ->where('pr.deleted = ?', false)
                ->where('tt.logDate > ?', $firstDay)
                ->where('tt.logDate < ?', $lastDay)
                ->where('tt.userId = ?', $this->getId())
                ->groupby('pr.urlKey');
        $projectTitles = App_Model_TaskTime::initialize($projectQuery);

        $returnArray = $taskArray = array();

        if ($projectTitles !== null) {
            foreach ($projectTitles as $project) {
                $taskQuery = App_Model_TaskTime::getQuery(array('tt.id'))
                        ->join('tb_task', 'tt.taskId = tk.id', 'tk',
                                array('tk.title', 'tk.urlKey', 'tk.id' => 'tId'))
                        ->where('tk.deleted = ?', false)
                        ->where('tk.projectId = ?', $project->pid)
                        ->where('tt.logDate > ?', $firstDay)
                        ->where('tt.logDate < ?', $lastDay)
                        ->where('tt.userId = ?', $this->getId())
                        ->groupby('tk.title');
                $taskTitles = App_Model_TaskTime::initialize($taskQuery);

                foreach ($taskTitles as $task) {
                    $time = App_Model_TaskTime::all(array(
                                'tt.taskId = ?' => $task->tId,
                                'tt.logDate > ?' => $firstDay,
                                'tt.logDate < ?' => $lastDay,
                                'tt.userId = ?' => $this->getId()
                                    ),
                            array('*'), 
                            array('tt.logDate' => 'asc'));

                    $taskArray[$task->getUrlKey() . '|' . $task->getTitle()] = $time;
                }

                $returnArray[$project->prUrlKey . '|' . $project->prTitle] = $taskArray;
                unset($taskQuery);
                unset($taskTitles);
                unset($taskArray);
            }
        }

        return $returnArray;
    }

    /**
     * 
     * @return type
     */
    public function getAssignedToProjects()
    {
        $query = self::getQuery(array('us.id'))
                ->join('tb_projectuser', 'us.id = pu.userId', 'pu', 
                        array('pu.userId', 'pu.projectId'))
                ->join('tb_project', 'pu.projectId = p.id', 'p',
                        array('p.id' => 'pId', 'p.title' => 'pTitle', 'p.urlKey' => 'pUrlKey',
                    'p.priority' => 'pPriority', 'p.created' => 'pCreated'))
                ->join('tb_state', 'p.stateId = s.id', 's', 
                        array('s.type' => 'stateType', 's.title' => 'stateTitle'))
                ->join('tb_client', 'p.clientId = c.id', 'c',
                        array('c.companyName', 'c.contactEmail', 'c.contactPerson'))
                ->join('tb_user', 'uss.id = p.managerId', 'uss', 
                        array('uss.firstname' => 'managerFname', 'uss.lastname' => 'managerLname'))
                ->where('pu.userId = ?', $this->getId())
                ->where('p.deleted = ?', false)
                ->order('p.priority', 'DESC')
                ->order('p.created', 'DESC');

        $result = self::initialize($query);
        return $result;
    }

}
