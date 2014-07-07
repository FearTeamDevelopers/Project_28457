<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_TaskRelated
 *
 * @author Tomy
 */
class App_Model_TaskRelated extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'tr';
    
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
    protected $_relatesTo;

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
