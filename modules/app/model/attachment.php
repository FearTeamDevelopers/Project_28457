<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Attachment
 *
 * @author Tomy
 */
class App_Model_Attachment extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'at';

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
     * @length 150
     *
     * @validate alphanumeric, max(60)
     * @label filename
     */
    protected $_filename;

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
     * @type integer
     *
     * @validate required, numeric
     * @label size
     */
    protected $_size;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 5
     *
     * @validate required, alphanumeric, max(5)
     * @label extension
     */
    protected $_ext;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     *
     * @validate required, path, max(250)
     * @label path
     */
    protected $_path;

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
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_path)) {
                return APP_PATH . $this->_path;
            } elseif (file_exists('.' . $this->_path)) {
                return '.' . $this->_path;
            } elseif (file_exists('./' . $this->_path)) {
                return './' . $this->_path;
            }
        } else {
            return $this->_path;
        }
    }

    /**
     * 
     * @return boolean
     */
    public function isImage()
    {
        if(in_array($this->_ext, array('gif', 'jpg', 'png', 'jpeg'))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 
     * @return type
     */
    public function getThumbPath()
    {
        if($this->isImage()){
            return str_replace('.'.$this->_ext, '_thumb.'.$this->_ext, $this->_path);
        }
    }

}
