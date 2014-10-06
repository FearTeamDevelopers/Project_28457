<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;

/**
 * 
 */
class File extends Base
{

    /**
     * @readwrite
     */
    protected $_filename;

    /**
     * @readwrite
     */
    protected $_size;

    /**
     * @readwrite
     */
    protected $_format;

    /**
     * @readwrite
     */
    protected $_modificationTime;

    /**
     * @readwrite
     */
    protected $_accessTime;

    /**
     * @readwrite
     */
    protected $_isExecutable;

    /**
     * @readwrite
     */
    protected $_isReadable;

    /**
     * @readwrite
     */
    protected $_isWritable;

    /**
     * 
     * @param type $options
     */
    public function __construct($file)
    {
        parent::__construct();

        $this->_filename = $file;
        $this->_loadMetaData();
    }

    /**
     * 
     */
    protected function _loadMetaData()
    {
        clearstatcache();

        $this->_format = strtolower(pathinfo($this->_filename, PATHINFO_EXTENSION));
        $this->_size = filesize($this->_filename);
        $this->_modificationTime = filemtime($this->_filename);
        $this->_accessTime = fileatime($this->_filename);
        $this->_isExecutable = is_executable($this->_filename);
        $this->_isReadable = is_readable($this->_filename);
        $this->_isWritable = is_writable($this->_filename);
    }

}
