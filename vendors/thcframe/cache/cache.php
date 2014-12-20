<?php

namespace THCFrame\Cache;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Cache\Exception;

/**
 * Cache factory class
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
     * 
     * @param type $method
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
        Event::fire('framework.cache.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('configuration');

            if (!empty($configuration->cache) && !empty($configuration->cache->type)) {
                $this->type = $configuration->cache->type;
                $this->options = (array) $configuration->cache;
            } else {
                $this->type = 'filecache';
                $this->options = array(
                    'mode' => 'active',
                    'duration' => 1800,
                    'suffix' => 'tmp',
                    'path' => 'temp/cache');
            }
        }

        Event::fire('framework.cache.initialize.after', array($this->type, $this->options));

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
