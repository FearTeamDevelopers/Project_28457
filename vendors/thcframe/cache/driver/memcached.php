<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Cache\Exception;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * Memcached stores data in memory, in hash lookup tables so the data is 
 * quickly accessible without reading it from disk. Memcached is an open source 
 * HTTP caching system that can store huge amounts of key/value data.
 */
class Memcached extends Cache\Driver
{

    protected $_service;

    /**
     * @readwrite
     */
    protected $_host = '127.0.0.1';

    /**
     * @readwrite
     */
    protected $_port = '11211';

    /**
     * @readwrite
     */
    protected $_isConnected = false;

    /**
     * @readwrite
     */
    protected $_duration;

    /**
     * Method is used to ensure that the value of the
     * $_service is a valid Memcached instance
     * 
     * @return boolean
     */
    protected function _isValidService()
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof \Memcache;

        if ($this->isConnected && $isInstance && !$isEmpty) {
            return true;
        }

        return false;
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        $this->connect();
        
        Event::add('framework.controller.destruct.after', function($name) {
            $cache = Registry::get('cache');
            $cache->disconnect();
        });
    }
    
    /**
     * Method attempts to connect to the Memcached server at the specified host/port
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function connect()
    {
        try {
            $this->_service = new \Memcache();
            $this->_service->connect(
                    $this->host, $this->port
            );

            $this->isConnected = true;
        } catch (\Exception $e) {
            throw new Exception\Service('Unable to connect to service');
        }

        return $this;
    }

    /**
     * Method attempts to disconnect the $_service instance from the Memcached service
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     */
    public function disconnect()
    {
        if ($this->_isValidService()) {
            $this->_service->close();
            $this->isConnected = false;
        }

        return $this;
    }

    /**
     * Get cached values
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws Exception\Service
     */
    public function get($key, $default = null)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $value = $this->_service->get($key, MEMCACHE_COMPRESSED);

        if ($value) {
            return $value;
        }

        return $default;
    }

    /**
     * Set values to keys
     * 
     * @param string $key
     * @param mixed $value
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function set($key, $value)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->set($key, $value, MEMCACHE_COMPRESSED, $this->duration);
        return $this;
    }

    /**
     * Erase value based on key param
     * 
     * @param string $key
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function erase($key)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->delete($key);
        return $this;
    }

    /**
     * Flush memcached
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function clearCache()
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->flush();
        return $this;
    }

    /**
     * Alias for clearCache
     */
    public function invalidate()
    {
        $this->clearCache();
    }

}
