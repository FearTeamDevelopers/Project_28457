<?php

namespace THCFrame\Request;

/**
 * Description of RequestMethods
 *
 * @author Tomy
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
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public static function get($key, $default = '')
    {
        if (!empty($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public static function post($key, $default = '')
    {
        if (isset($_POST[$key]) && !empty($_POST[$key])) {
            return $_POST[$key];
        }
        return $default;
    }
    
    /**
     * 
     * @param type $key
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
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public static function server($key, $default = '')
    {
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public static function cookie($key, $default = '')
    {
        if (!empty($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        return $default;
    }

}
