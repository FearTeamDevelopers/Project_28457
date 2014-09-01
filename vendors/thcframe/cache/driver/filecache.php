<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Cache\Exception;
use THCFrame\Filesystem\FileManager;

/**
 * Class handles operations with file cache
 *
 * @author Tomy
 */
class Filecache extends Cache\Driver
{

    /**
     * @readwrite
     */
    protected $_duration;
    
    /**
     * @readwrite
     */
    protected $_path;
    
    /**
     * @readwrite
     */
    protected $_suffix;
    
    /**
     * @readwrite
     */
    protected $_mode;
    
    /**
     *
     * @var type 
     */
    private $_fileManager;

    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_fileManager = new FileManager();
        $this->_path = '.'.DIRECTORY_SEPARATOR.$this->_path.DIRECTORY_SEPARATOR;
        $this->_suffix = '.'.trim($this->_suffix, '.');

        if (!is_dir($this->_path)) {
            $this->_fileManager->mkdir($this->_path, 0755);
        }
    }

    /**
     * Method checks if cache file is not expired
     * 
     * @param string $key
     * @return boolean
     */
    public function isFresh($key)
    {
        if ($this->_mode == 'active' && ENV == 'dev') {
            return false;
        }

        if (file_exists($this->_path . $key . $this->_suffix)) {
            if (time() - filemtime($this->_path . $key . $this->_suffix) <= $this->duration) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Loads cache file content
     * 
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get($key, $default = null)
    {
        if ($this->isFresh($key)) {
            $data = unserialize(file_get_contents($this->_path . $key . $this->_suffix));
            return $data;
        } else {
            return $default;
        }
    }

    /**
     * Save data into file named by key
     * 
     * @param string $key
     * @param mixed $value
     * @return type
     * @throws Exception\Service
     */
    public function set($key, $value)
    {
        $file = $this->_path . $key . $this->_suffix;
        $tmpFile = tempnam($this->_path, basename($key . $this->_suffix));

        if (false !== @file_put_contents($tmpFile, serialize($value)) && $this->_fileManager->rename($tmpFile, $file, true)) {
            $this->_fileManager->chmod($file, 0666, umask());

            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

            return;
        }

        throw new Exception\Service(sprintf('Failed to write cache file %s', $file));
    }

    /**
     * Removes file with specific name
     * 
     * @param string $key
     */
    public function erase($key)
    {
        if (file_exists($this->_path . $key . $this->_suffix)) {
            $this->_fileManager->remove($this->_path . $key . $this->_suffix);
        }
    }

    /**
     * Removes all files and folders from cache folder
     */
    public function clearCache()
    {
        $this->_fileManager->remove($this->_path);
        return;
    }

    /**
     * 
     */
    public function invalidate()
    {
        $this->clearCache();
    }

}
