<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_TaskChat
 *
 * @author Tomy
 */
class App_Model_TaskChat extends Model
{
    /**
     * @readwrite
     */
    protected $_alias = 'tc';
    
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
    protected $_taskId;

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
     * @type integer
     * @index
     * 
     * @validate numeric, max(8)
     * @label reply
     */
    protected $_reply;

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
     * @type boolean
     * 
     * @validate max(3)
     * @label public
     */
    protected $_isPublic;

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
     * @label text
     */
    protected $_body;

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

    /**
     * 
     */
    public function getReplies()
    {
        return self::all(
                        array(
                    'reply = ?' => $this->getId(),
                    'active = ?' => true,
                        ), array('*'), array('created' => 'desc'));
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchReplies($id)
    {
        $message = new self(array(
            'id' => $id
        ));

        return $message->getReplies();
    }
}
