<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_TaskChangeLog
 *
 * @author Tomy
 */
class App_Model_TaskChangeLog extends Model
{
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
    protected $_originalTaskId;
    
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
     * @validate required, alphanumeric, max(4096)
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
    protected $_type;

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
     * @label time to resolve
     */
    protected $_timeToResolve;

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
}
