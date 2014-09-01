<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Database\Exception;

/**
 * Description of Connector
 * Factory allows many different kinds of configuration driver classes to be used, 
 * we need a way to share code across all driver classes.
 *
 * @author Tomy
 */
abstract class Connector extends Base
{

    /**
     * 
     * @return \THCFrame\Database\Connector
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

    public abstract function connect();

    public abstract function disconnect();

    public abstract function query();

    public abstract function execute($sql);

    public abstract function escape($value);

    public abstract function getLastInsertId();

    public abstract function getAffectedRows();

    public abstract function getLastError();

    public abstract function sync(\THCFrame\Model\Model $model);
}
