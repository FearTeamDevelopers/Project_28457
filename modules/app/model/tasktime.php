<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_TaskTime
 *
 * @author Tomy
 */
class App_Model_TaskTime extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'tt';

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
     * @index
     * @unique
     * 
     * @validate required, numeric, max(8)
     */
    protected $_taskId;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * @unique
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric
     */
    protected $_spentTime;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate alphanumeric, max(4096)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     *
     * @validate date, max(15)
     * @label date
     */
    protected $_logDate;

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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $projectId
     */
    public static function fetchTotalUserTimeByProject($projectId)
    {
        $timeQuery = self::getQuery(array('SUM(tt.spentTime)' => 'sptime'))
                ->join('tb_task', 'tt.taskId = tk.id', 'tk', 
                        array('tk.projectId'))
                ->join('tb_user', 'tt.userId = us.id', 'us', 
                        array('us.id' => 'usId', 'us.firstname', 'us.lastname'))
                ->where('tk.projectId = ?', (int) $projectId)
                ->where('tk.deleted = ?', false)
                ->groupby('usId');

        $timeArr = self::initialize($timeQuery);

        if ($timeArr !== null) {
            foreach ($timeArr as $key => $time) {
                $zero = new DateTime('@0');
                $offset = new DateTime('@' . (int) $time->sptime * 60);
                $diff = $zero->diff($offset);
                $time->sptime = $diff->format('%d days, %h:%i');
                $timeArr[$key] = $time;
            }

            return $timeArr;
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $projectId
     */
    public static function fetchTotalTimeByProject($projectId)
    {
        $timeQuery = self::getQuery(array("SUM(tt.spentTime)" => 'sptime'))
                ->join('tb_task', 'tt.taskId = tk.id', 'tk', 
                        array('tk.projectId'))
                ->where('tk.projectId = ?', (int) $projectId)
                ->where('tk.deleted = ?', false);

        $timeArr = self::initialize($timeQuery);

        if ($timeArr !== null) {
            $time = array_shift($timeArr);

            $zero = new DateTime('@0');
            $offset = new DateTime('@' . (int) $time->sptime * 60);
            $diff = $zero->diff($offset);
            $time->sptime = $diff->format('%d days, %h:%i');

            return $time;
        } else {
            return null;
        }
    }

}
