<?php

namespace THCFrame\Session\Driver;

use THCFrame\Session;

/**
 * Description of Server
 *
 * @author Tomy
 */
class Server extends Session\Driver
{

    /**
     * @readwrite
     */
    protected $_prefix;
    
    /**
     * @readwrite
     */
    protected $_ttl;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        @session_start();
        
        if($this->get('origin') === null){
            $this->set('origin', time());
            $this->clearExpiredSession();
        }
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public function get($key, $default = null)
    {
        if (isset($_SESSION[$this->prefix . $key])) {
            return $_SESSION[$this->prefix . $key];
        }

        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @return \THCFrame\Session\Driver\Server
     */
    public function set($key, $value)
    {
        $_SESSION[$this->prefix . $key] = $value;
        return $this;
    }

    /**
     * 
     * @param type $key
     * @return \THCFrame\Session\Driver\Server
     */
    public function erase($key)
    {
        unset($_SESSION[$this->prefix . $key]);
        return $this;
    }

    /**
     * 
     * @return \THCFrame\Session\Driver\Server
     */
    public function clear()
    {
        $_SESSION = array();
        return $this;
    }
    
    /**
     * 
     */
    public function clearExpiredSession()
    {
        if(time() - $this->get('origin') > $this->ttl){
            $this->clear();
            @session_regenerate_id();
        }
    }
}
