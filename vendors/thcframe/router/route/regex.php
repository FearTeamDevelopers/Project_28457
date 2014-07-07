<?php

namespace THCFrame\Router\Route;

use THCFrame\Router as Router;

/**
 * Description of Regex
 *
 * @author Tomy
 */
class Regex extends Router\Route
{

    /**
     * @readwrite
     */
    protected $_keys;

    /**
     * 
     * @param type $url
     * @return boolean
     */
    public function matches($url)
    {
        $pattern = $this->pattern;

        // check values
        preg_match_all("#^{$pattern}$#", $url, $values);

        if (count($values) && count($values[0]) && count($values[1])) {
            // values found, modify parameters and return
            $derived = array_combine($this->keys, $values[1]);
            $this->parameters = array_merge($this->parameters, $derived);

            return true;
        }

        return false;
    }

}
