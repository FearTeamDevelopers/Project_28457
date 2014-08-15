<?php

namespace THCFrame\Cache;

use THCFrame\Core\Base as Base;
use THCFrame\Cache\Exception as Exception;

/**
 * Description of Driver
 * Factory allows many different kinds of configuration driver classes to be used, 
 * we need a way to share code across all driver classes.
 * 
 * @author Tomy
 */
abstract class Driver extends Base
{

    /**
     * 
     * @return \THCFrame\Cache\Driver
     */
    public function initialize()
    {
        return $this;
    }

    /**
     * Throw exception if specific method is no implemented
     * 
     * @param string $method
     * @return \THCFrame\Cache\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    abstract function get($key, $default = null);

    abstract function set($key, $value);

    abstract function erase($key);

    abstract function clearCache();
    
    abstract function invalidate();
}
