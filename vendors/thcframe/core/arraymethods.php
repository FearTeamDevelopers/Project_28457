<?php

namespace THCFrame\Core;

/**
 * ArrayMethods class
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
     * Remove all values considered empty() and returns the resultant array
     * 
     * @param array $array
     * @return array
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
     * @param array $array
     * @return array
     */
    public static function trim($array)
    {
        return array_map(function($item) {
            return trim($item);
        }, $array);
    }

    /**
     * Return first value of an array
     * 
     * @param array $array
     * @return mixed
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
     * Return last value of an array
     * 
     * @param array $array
     * @return mixed
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
     * Convert array to object
     * 
     * @param array $array
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
     * @param array $array
     * @param array $return
     * @return array
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
     * Create query string form array
     * 
     * @param array $array
     * @return string
     */
    public static function toQueryString($array)
    {
        return http_build_query(self::clean($array));
    }

}
