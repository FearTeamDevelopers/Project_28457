<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Cache\Exception as Exception;
use THCFrame\Filesystem\FileManager as FileManager;

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
    private $_cacheFilePath;
    private $_fileSuffix;
    private $_fileManager;

    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $configuration = Registry::get('config');

        if (!empty($configuration->cache->filecache)) {
            $this->_cacheFilePath = APP_PATH . '/' . $configuration->cache->filecache->path . '/';
            $this->_fileSuffix = '.' . $configuration->cache->filecache->suffix;
            $this->_fileManager = new FileManager();

            if (!is_dir($this->_cacheFilePath)) {
                $this->_fileManager->mkdir($this->_cacheFilePath, 0777);
            }
        } else {
            throw new \Exception('Error in configuration file');
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
        if (ENV == 'dev') {
            return false;
        }

        if (file_exists($this->_cacheFilePath . $key . $this->_fileSuffix)) {
            if (time() - filemtime($this->_cacheFilePath . $key . $this->_fileSuffix) <= $this->duration) {
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
            $data = unserialize(file_get_contents($this->_cacheFilePath . $key . $this->_fileSuffix));
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
        $file = $this->_cacheFilePath . $key . $this->_fileSuffix;
        $tmpFile = tempnam($this->_cacheFilePath, basename($key . $this->_fileSuffix));

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
        if (file_exists($this->_cacheFilePath . $key . $this->_fileSuffix)) {
            $this->_fileManager->remove($this->_cacheFilePath . $key . $this->_fileSuffix);
        }
    }

    /**
     * Removes all files and folders from cache folder
     */
    public function clearCache()
    {
        $this->_fileManager->remove($this->_cacheFilePath);
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
