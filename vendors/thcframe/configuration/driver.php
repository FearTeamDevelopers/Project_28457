<?php

namespace THCFrame\Configuration;

use THCFrame\Core\Base;
use THCFrame\Configuration\Exception;

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
     * @readwrite
     * @var type 
     */
    protected $_env;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Configuration\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }
    
    /**
     * 
     * @return \THCFrame\Configuration\Driver
     */
    public function initialize()
    {
        return $this;
    }

    protected abstract function _parse($path);

    protected abstract function _parseDefault($path);
}
