<?php

namespace THCFrame\Configuration;

use THCFrame\Core\Base as Base;
use THCFrame\Configuration\Exception as Exception;

/**
 * Description of Driver
 * Factory allows many different kinds of configuration driver classes to be used, 
 * we need a way to share code across all driver classes.
 *
 * @author Tomy
 */
abstract class Driver extends Base
{

    protected $_parsed;
    
    /**
     * @readwrite
     */
    protected $_env;

    /**
     * 
     * @return \THCFrame\Configuration\Driver
     */
    public function initialize()
    {
        return $this;
    }

    /**
     * 
     * @param string $method
     * @return \THCFrame\Configuration\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

}
