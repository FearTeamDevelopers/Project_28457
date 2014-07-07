<?php

namespace THCFrame\Cache;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Cache\Exception as Exception;

/**
 * Factory class
 * 
 * @author Tomy
 */
class Cache extends Base
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
     * @return \THCFrame\Cache\Exception\Implementation
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
     * @return \THCFrame\Cache\Cache\Driver\Memcached
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Events::fire('framework.cache.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('config');

            if (!empty($configuration->cache) && !empty($configuration->cache->type)) {
                $this->type = $configuration->cache->type;
                unset($configuration->cache->type);
                $this->options = (array) $configuration->cache;
            } else {
                throw new \Exception('Error in configuration file');
            }
        }

        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Events::fire('framework.cache.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'memcached': {
                    return new Driver\Memcached($this->options);
                    break;
                }
            case 'filecache': {
                    return new Driver\Filecache($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid type');
                    break;
                }
        }
    }

}
