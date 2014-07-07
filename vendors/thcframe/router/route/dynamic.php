<?php

namespace THCFrame\Router\Route;

use THCFrame\Router as Router;

/**
 * Description of Dynamic
 *
 */
class Dynamic extends Router\Route
{

    /**
     * Stores any set dynamic elements
     * 
     * @var array
     * @readwrite
     */
    protected $_dynamicElements = array();

    /**
     * Stores any arguments found when mapping
     * 
     * @var array 
     * @readwrite
     */
    protected $_mapArguments = array();

    /**
     * Adds a found argument to the _mapArguments array
     * 
     * @param string $key
     * @param mixed $value
     */
    private function _addMapArguments($key, $value)
    {
        $this->_mapArguments[$key] = $value;
    }

    /**
     * Adds a dynamic element to the Route
     * 
     * @param string $key
     * @param mixed $value
     * @return \THCFrame\Router\Route\Dynamic
     */
    public function addDynamicElement($key, $value)
    {
        $this->_dynamicElements[$key] = $value;

        return $this;
    }

    /**
     * Get the dynamic elements array
     * 
     * @return array
     */
    public function getDynamicElements()
    {
        return $this->_dynamicElements;
    }

    /**
     * Gets the _mapArguments array
     * 
     * @return array
     */
    public function getMapArguments()
    {
        return $this->_mapArguments;
    }

    /**
     * Attempt to match this route to a supplied path
     * 
     * @param string $path_to_match
     * @return boolean
     */
    public function matchMap($path_to_match)
    {
        $found_dynamic_module = NULL;
        $found_dynamic_class = NULL;
        $found_dynamic_method = NULL;
        $found_dynamic_args = array();

        //Ignore query parameters during matching
        $parsed = parse_url($path_to_match);
        $path_to_match = $parsed['path'];

        //The process of matching is easier if there are no preceding slashes
        $temp_this_path = preg_replace('/^\//', '', $this->pattern);
        $temp_path_to_match = preg_replace('/^\//', '', $path_to_match);

        //Get the path elements used for matching later
        $this_path_elements = explode('/', $temp_this_path);
        $match_path_elements = explode('/', $temp_path_to_match);

        //If the number of elements in each path is not the same, there is no
        // way this could be it.
        if (count($this_path_elements) !== count($match_path_elements))
            return FALSE;

        //Construct a path string that will be used for matching
        $possible_match_string = '';
        foreach ($this_path_elements as $i => $this_path_element) {
            // ':'s are never allowed at the beginning of the path element
            if (preg_match('/^:/', $match_path_elements[$i])) {
                return FALSE;
            }

            //This element may simply be static, if so the direct comparison
            // will discover it.
            if ($this_path_element === $match_path_elements[$i]) {
                $possible_match_string .= "/{$match_path_elements[$i]}";
                continue;
            }

            //Consult the dynamic array for help in matching
            if (TRUE === isset($this->_dynamicElements[$this_path_element])) {
                //The dynamic array either contains a key like ':id' or a
                // regular expression. In the case of a key, the key matches
                // anything
                if ($this->_dynamicElements[$this_path_element] === $this_path_element) {
                    $possible_match_string .= "/{$match_path_elements[$i]}";

                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if (':module' === $this_path_element && NULL === $this->module) {
                        $found_dynamic_module = $match_path_elements[$i];
                    } elseif (':controller' === $this_path_element && NULL === $this->controller) {
                        $found_dynamic_class = $match_path_elements[$i];
                    } elseif (':action' === $this_path_element && NULL === $this->action) {
                        $found_dynamic_method = $match_path_elements[$i];
                    } elseif (':module' !== $this_path_element && ':controller' !== $this_path_element && ':action' !== $this_path_element) {
                        $found_dynamic_args[$this_path_element] = $match_path_elements[$i];
                    }

                    continue;
                }

                //Attempt a regular expression match
                $regexp = '/' . $this->_dynamicElements[$this_path_element] . '/';
                if (preg_match($regexp, $match_path_elements[$i]) > 0) {
                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if (':module' === $this_path_element && NULL === $this->module) {
                        $found_dynamic_module = $match_path_elements[$i];
                    } elseif (':controller' === $this_path_element && NULL === $this->controller) {
                        $found_dynamic_class = $match_path_elements[$i];
                    } elseif (':method' === $this_path_element && NULL === $this->action) {
                        $found_dynamic_method = $match_path_elements[$i];
                    } elseif (':module' !== $this_path_element && ':controller' !== $this_path_element && ':action' !== $this_path_element) {
                        $found_dynamic_args[$this_path_element] = $match_path_elements[$i];
                    }

                    $possible_match_string .= "/{$match_path_elements[$i]}";

                    continue;
                }
            }

            // In order for a full match to succeed, all iterations must match.
            // Because we are continuing with the next loop if any conditions
            // above are met, if this point is reached, this route cannot be
            // a match.
            return FALSE;
        }

        //Do the final comparison and return the result
        if ($possible_match_string === $path_to_match) {
            if (NULL !== $found_dynamic_module) {
                $this->setModule($found_dynamic_module);
            }

            if (NULL !== $found_dynamic_class) {
                $this->setController($found_dynamic_class);
            }

            if (NULL !== $found_dynamic_method) {
                $this->setAction($found_dynamic_method);
            }

            foreach ($found_dynamic_args as $key => $found_dynamic_arg) {
                $this->_addMapArguments($key, $found_dynamic_arg);
            }
        }

        return ( $possible_match_string === $path_to_match );
    }

}