<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Database\Exception;

/**
 * Factory class returns a Database\Connector subclass.
 * Connectors are the classes that do the actual interfacing with the 
 * specific database engine. They execute queries and return data
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
     * 
     * @param type $method
     * @return \THCFrame\Session\Exception\Implementation
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
     * @return \THCFrame\Database\Database\Connector
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Event::fire('framework.database.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('configuration');

            if (!empty($configuration->database) && !empty($configuration->database->type)) {
                $this->type = $configuration->database->type;
                $this->options = (array) $configuration->database;
            } else {
                throw new Exception\Argument('Error in configuration file');
            }
        }

        Event::fire('framework.database.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'mysql': {
                    return new Connector\Mysql($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid database type');
                    break;
                }
        }
    }

}
