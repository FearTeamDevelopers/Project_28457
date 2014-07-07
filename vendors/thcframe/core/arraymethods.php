<?php

namespace THCFrame\Core;

/**
 * Description of ArrayMethods
 *
 * @author Tomy
 */
class ArrayMethods
{

    /**
     * 
     */
    private function __construct()
    {
        
    }

    /**
     * 
     */
    private function __clone()
    {
        
    }

    /**
     * Method removes all values considered empty() and returns the resultant array
     * 
     * @param type $array
     * @return type
     */
    public static function clean($array)
    {
        return array_filter($array, function($item) {
            return !empty($item);
        });
    }

    /**
     * Method returns an array, which contains all the items of the initial array, 
     * after they have been trimmed of all whitespace
     * 
     * @param type $array
     * @return type
     */
    public static function trim($array)
    {
        return array_map(function($item) {
            return trim($item);
        }, $array);
    }

    /**
     * 
     * @param type $array
     * @return null
     */
    public static function first($array)
    {
        if (count($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[0]];
    }

    /**
     * 
     * @param type $array
     * @return null
     */
    public static function last($array)
    {
        if (count($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[count($keys) - 1]];
    }

    /**
     * 
     * @param type $array
     * @return \stdClass
     */
    public static function toObject($array)
    {
        $result = new \stdClass();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result->{$key} = self::toObject($value);
            } else {
                $result->{$key} = $value;
            }
        }

        return $result;
    }

    /**
     * 
     * @param type $array
     * @param type $return
     * @return type
     */
    public static function flatten($array, $return = array())
    {
        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $return = self::flatten($value, $return);
            } else {
                $return[] = $value;
            }
        }

        return $return;
    }

    /**
     * 
     * @param type $array
     * @return type
     */
    public static function toQueryString($array)
    {
        return http_build_query(self::clean($array));
    }

}
