<?php

namespace THCFrame\Module;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Module\Exception as Exception;
use THCFrame\Router\Route as Route;

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

        Events::fire('framework.module.initialize.before', array($this->moduleName));

        Events::fire('framework.module.initialize.after', array($this->moduleName));
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

    /**
     * nepouzivana
     */
    public function loadModuleRoutes()
    {
        $router = Registry::get('router');

        foreach ($this->_routes as $route) {
            $new_route = new Route\Dynamic(array('pattern' => $route['pattern']));

            if (preg_match('/^:/', $route['module'])) {
                $new_route->addDynamicElement(':module', ':module');
            } else {
                $new_route->setModule($route['module']);
            }

            if (preg_match('/^:/', $route['controller'])) {
                $new_route->addDynamicElement(':controller', ':controller');
            } else {
                $new_route->setController($route['controller']);
            }

            if (preg_match('/^:/', $route['action'])) {
                $new_route->addDynamicElement(':action', ':action');
            } else {
                $new_route->setAction($route['action']);
            }

            if (isset($route['args']) && preg_match('/^:/', $route['args'])) {
                $new_route->addDynamicElement($route['args'], $route['args']);
            }

            $router->addRoute($new_route);
        }
    }

}
