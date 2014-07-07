<?php

namespace THCFrame\Session;

use THCFrame\Core\Base as Base;
use THCFrame\Session\Exception as Exception;

/**
 * Description of Driver
 *
 * @author Tomy
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

    abstract function get($key, $default = null);

    abstract function set($key, $value);

    abstract function erase($key);
}
