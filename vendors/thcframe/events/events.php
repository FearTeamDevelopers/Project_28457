<?php

namespace THCFrame\Events;

/**
 * Events
 * 
 * @author Tomy
 */
class Events
{

    private static $_callbacks = array();

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * Add new event and callback function
     * 
     * @param string $type
     * @param function $callback
     */
    public static function add($type, $callback)
    {
        if (empty(self::$_callbacks[$type])) {
            self::$_callbacks[$type] = array();
        }
        self::$_callbacks[$type][] = $callback;
    }

    /**
     * Call specific event callback function with provided parameters
     * 
     * @param string $type
     * @param mixed $parameters
     */
    public static function fire($type, $parameters = null)
    {
        if (!empty(self::$_callbacks[$type])) {
            foreach (self::$_callbacks[$type] as $callback) {
                call_user_func_array($callback, $parameters);
            }
        }
    }

    /**
     * Remove event from _callbacks array
     * 
     * @param string $type
     * @param string $callback
     */
    public static function remove($type, $callback)
    {
        if (!empty(self::$_callbacks[$type])) {
            foreach (self::$_callbacks[$type] as $i => $found) {
                if ($callback == $found) {
                    unset(self::$_callbacks[$type][$i]);
                }
            }
        }
    }

}
