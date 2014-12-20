<?php

namespace THCFrame\Request;

/**
 * Request methods wrapper class
 */
class RequestMethods
{

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * Get value from $_GET array
     * 
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = '')
    {
        if (isset($_GET[$key]) && (!empty($_GET[$key]) || is_numeric($_GET[$key]))) {
            return $_GET[$key];
        }
        return $default;
    }

    /**
     * Get value from $_POST array
     * 
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function post($key, $default = '')
    {
        if (isset($_POST[$key]) && (!empty($_POST[$key]) || is_numeric($_POST[$key]))) {
            return $_POST[$key];
        }
        return $default;
    }
    
    /**
     * Check if key is in $_POST array
     * 
     * @param mixed $key
     * @return boolean
     */
    public static function issetpost($key)
    {
        if (isset($_POST[$key])) {
            return true;
        }
        return false;
    }

    /**
     * Get value from $_SERVER array
     * 
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function server($key, $default = '')
    {
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }

    /**
     * Get value from $_COOKIE array
     * 
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function cookie($key, $default = '')
    {
        if (!empty($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        return $default;
    }

}
