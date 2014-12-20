<?php

namespace THCFrame\Session;

use THCFrame\Core\Base;
use THCFrame\Session\Exception;

/**
 * Factory allows many different kinds of configuration driver classes to be used, 
 * we need a way to share code across all driver classes.
 */
abstract class Driver extends Base
{

    /**
     * 
     * @return \THCFrame\Session\Driver
     */
    public function initialize()
    {
        return $this;
    }

    /**
     * 
     * @param type $method
     * @return \THCFrame\Session\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    public abstract function get($key, $default = null);

    public abstract function set($key, $value);

    public abstract function erase($key);
    
    public abstract function clear();
}
