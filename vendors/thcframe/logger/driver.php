<?php

namespace THCFrame\Logger;

use THCFrame\Core\Base;
use THCFrame\Logger\Exception;

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
    protected $_path;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_syslog;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_errorlog;
    
    /**
     * 
     * @return \THCFrame\Cache\Driver
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

    public abstract function log($message);
    
    public abstract function logError($message);
    
    public abstract function deleteOldLogs($olderThan);
    
}