<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Calendar
 *
 * @author Tomy
 */
class App_Model_Calendar extends Model
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
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     * @label active
     */
    protected $_active;

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
     * @validate required, alphanumeric, max(2048)
     * @label description
     */
    protected $_description;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     *
     * @validate required, url, max(250)
     * @label link
     */
    protected $_link;

    /**
     * @column
     * @readwrite
     * @type datetime
     * 
     * @validate required, datetime, max(25)
     * @label start date
     */
    protected $_startDate;

    /**
     * @column
     * @readwrite
     * @type datetime
     * 
     * @validate required, datetime, max(25)
     * @label end date
     */
    protected $_endDate;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     */
    protected $_isPublic;
    
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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}
