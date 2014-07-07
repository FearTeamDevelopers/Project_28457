<?php

namespace THCFrame\Router\Route;

use THCFrame\Router as Router;
use THCFrame\Core\ArrayMethods as ArrayMethods;

/**
 * Description of Simple
 *
 * @author Tomy
 */
class Simple extends Router\Route
{

    /**
     * 
     * @param type $url
     * @return boolean
     */
    public function matches($url)
    {
        $pattern = $this->pattern;

        // get keys
        preg_match_all("#:([a-zA-Z0-9]+)#", $pattern, $keys);

        if (count($keys) && count($keys[0]) && count($keys[1])) {
            $keys = $keys[1];
        } else {
            // no keys in the pattern, return a simple match
            return preg_match("#^{$pattern}$#", $url);
        }

        // normalize route pattern
        $pattern = preg_replace("#(:[a-zA-Z0-9]+)#", "([a-zA-Z0-9-_]+)", $pattern);

        // check values
        preg_match_all("#^{$pattern}$#", $url, $values);

        if (count($values) && count($values[0]) && count($values[1])) {
            // unset the matched url
            unset($values[0]);

            // values found, modify parameters and return
            $derived = array_combine($keys, ArrayMethods::flatten($values));
            $this->parameters = array_merge($this->parameters, $derived);

            return true;
        }

        return false;
    }

}
