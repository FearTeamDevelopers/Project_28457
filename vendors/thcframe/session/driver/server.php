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
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        @session_start();
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
}
