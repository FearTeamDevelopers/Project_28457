<?php

namespace THCFrame\Module;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Module\Exception;
use THCFrame\Events\SubscriberInterface;

/**
 * Description of Module
 *
 * @author Tomy
 */
class Module extends Base
{

    /**
     * @read
     */
    protected $_moduleName;

    /**
     * @read
     */
    protected $_observerClass;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        Event::fire('framework.module.initialize.before', array($this->moduleName));

        $this->addModuleEvents();
        
        Event::add('framework.router.construct.after', function($router){
            $router->createRoutes($this->getModuleRoutes());
        });

        Event::fire('framework.module.initialize.after', array($this->moduleName));
    }

    /**
     * 
     */
    private function addModuleEvents()
    {
        $mo = $this->getObserverClass();

        if (isset($mo) && $mo != '') {
            $moduleObserver = new $mo();

            if ($moduleObserver instanceof SubscriberInterface) {
                $events = $moduleObserver->getSubscribedEvents();

                foreach ($events as $name => $callback) {
                    if(is_array($callback)){
                        foreach ($callback as $call){
                            Event::add($name, array($moduleObserver, $call));
                        }
                    }else{
                        Event::add($name, array($moduleObserver, $callback));
                    }

                    
                }
            }
        }
    }

    /**
     * 
     * @param type $method
     * @return \THCFrame\Module\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @return type
     */
    public function getModuleRoutes()
    {
        return $this->_routes;
    }

}
