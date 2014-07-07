<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_State
 *
 * @author Tomy
 */
class App_Model_State extends Model
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
     * @length 50
     *
     * @validate required, alpha, max(50)
     * @label type
     */
    protected $_type;
    
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
