<?php

namespace THCFrame\Database;

use THCFrame\Core\Base as Base;
use THCFrame\Database\Exception as Exception;

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
     * @param string $method
     * @return \THCFrame\Database\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    abstract function connect();

    abstract function disconnect();

    abstract function query();

    abstract function execute($sql);

    abstract function escape($value);

    abstract function getLastInsertId();

    abstract function getAffectedRows();

    abstract function getLastError();

    abstract function sync(\THCFrame\Model\Model $model);
}
