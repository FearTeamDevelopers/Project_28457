<?php

namespace THCFrame\Events;

use THCFrame\Registry\Registry;


/**
 * Observer
 * 
 * @author Tomy
 */
class Events
{

    private static $_callbacks = array();
    private static $_instatnce = null;

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * 
     */
    public static function initialize()
    {
        
        $configuration = Registry::get('config');
        
        if (!empty($configuration->observer->event)) {
            $events = (array) $configuration->observer->event;

            foreach ($events as $event => $callback) {
                self::add($event, $callback);
                
            }
        }
    }

    /**
     * 
     * @param type $type
     * @param type $callback
     */
    public static function add($type, $callback)
    {
        if (empty(self::$_callbacks[$type])) {
            self::$_callbacks[$type] = array();
        }

        self::$_callbacks[$type][] = $callback;
    }

    /**
     * 
     * @param type $type
     * @param type $parameters
     */
    public static function fire($type, $parameters = null)
    {
        if (!empty(self::$_callbacks[$type])) {
            foreach (self::$_callbacks[$type] as $callback) {
                if (is_callable($callback)) {
                    call_user_func_array($callback, $parameters);
                } else {
                    $parts = explode('.', $type);
                    $moduleObject = \THCFrame\Core\Core::getModule($parts[0]);
                    $observerClass = $moduleObject->getObserverClass();
                    $observer = Registry::get($observerClass);

                    if ($observer === null) {
                        $observer = new $observerClass;
                        Registry::set($observerClass, $observer);
                    }

                    $observer->$callback($parameters);
                }
            }
        }
    }

    /**
     * 
     * @param type $type
     * @param type $callback
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
