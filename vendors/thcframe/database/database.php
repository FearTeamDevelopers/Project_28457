<?php

namespace THCFrame\Database;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
//use THCFrame\Database\Database as Database;
use THCFrame\Database\Exception as Exception;

/**
 * Factory class returns a Database\Connector subclass 
 * (in this case Database\Connector\Mysql). 
 * Connectors are the classes that do the actual interfacing with the 
 * specific database engine. They execute queries and return metadata
 * 
 * @author Tomy
 */
class Database extends Base
{

    /**
     * @readwrite
     */
    protected $_type;

    /**
     * @readwrite
     */
    protected $_options;

    /**
     * Throw exception if specific method is not implemented
     * 
     * @param string $method
     * @return \THCFrame\Database\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Factory method
     * It accepts initialization options and selects the type of returned object, 
     * based on the internal $_type property.
     * 
     * @return \THCFrame\Database\Database\Connector\Mysql
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Events::fire('framework.database.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('config');

            if (!empty($configuration->database) && !empty($configuration->database->type)) {
                $this->type = $configuration->database->type;
                unset($configuration->database->type);
                $this->options = (array) $configuration->database;
            } else {
                throw new \Exception('Error in configuration file');
            }
        }

        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Events::fire('framework.database.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'mysql': {
                    return new Connector\Mysql($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid type');
                    break;
                }
        }
    }

}
