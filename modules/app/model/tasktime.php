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

}
