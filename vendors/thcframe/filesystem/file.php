<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;
use THCFrame\Filesystem\Exception as Exception;
use THCFrame\Core\StringMethods as StringMethods;

/**
 * 
 */
class File extends Base
{

    /**
     * @readwrite
     */
    protected $_file;

    /**
     * @readwrite
     */
    protected $_originalInfo;

    /**
     * 
     * @param type $options
     */
    public function __construct($file)
    {
        parent::__construct();

        $this->_file = $file;
        $this->_getMetaData();
    }

    /**
     * 
     */
    public function getDataForDb()
    {
        return $this->_originalInfo;
    }

    /**
     * 
     */
    protected function _getMetaData()
    {
        clearstatcache();

        $this->_originalInfo = array(
            'path' => $this->_file,
            'filename' => pathinfo($this->_file, PATHINFO_FILENAME),
            'size' => filesize($this->_file),
            'ext' => strtolower(pathinfo($this->_file, PATHINFO_EXTENSION)),
            'modificationTime' => filemtime($this->_file),
            'accessTime' => fileatime($this->_file),
            'isDir' => is_dir($this->_file),
            'isFile' => is_file($this->_file),
            'isExecutable' => is_executable($this->_file),
            'isReadable' => is_readable($this->_file),
            'isWritable' => is_writable($this->_file)
        );
    }

}
