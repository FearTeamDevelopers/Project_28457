<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_AdminLog
 *
 * @author Tomy
 */
class App_Model_AdminLog extends Model
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
     * @type text
     * @length 80
     * 
     * @validate alphanumeric, max(80)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     * 
     * @validate alpha, max(50)
     */
    protected $_module;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     * 
     * @validate alpha, max(50)
     */
    protected $_controller;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     * 
     * @validate alpha, max(50)
     */
    protected $_action;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * 
     * @validate alpha, max(15)
     */
    protected $_result;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     */
    protected $_params;

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
