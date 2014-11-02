<?php

namespace THCFrame\Security\Model;

use THCFrame\Model\Model;

/**
 * Authtoken used by "Remember me" function
 */
class Authtoken extends Model
{
    /**
     * @readwrite
     */
    protected $_alias = 'auth';
    
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
    protected $_userId;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 130
     * @index
     * @unique
     *
     * @validate required, alphanumeric, max(130)
     * @label auth token
     */
    protected $_token;
    
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
        $raw = $primary["raw"];

        if (empty($this->$raw)) {
            $this->setCreated(date("Y-m-d H:i:s"));
        }
        $this->setModified(date("Y-m-d H:i:s"));
    }
}
