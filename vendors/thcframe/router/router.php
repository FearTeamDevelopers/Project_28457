<?php

namespace THCFrame\Router;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Router\Exception;
use THCFrame\Router\Route;

/**
 * Router class
 */
class Router extends Base
{

    /**
     * @readwrite
     */
    protected $_url;

    /**
     * Stores the Route objects
     * @var array
     */
    protected $_routes = array();

    /**
     * @readwrite 
     * @var Route
     */
    protected $_lastRoute;
    
    /**
     * Application default routes
     * 
     * @var array
     */
    private static $_defaultRoutes = array(
        array(
            'pattern' => '/:module/:controller/:action/:id',
            'module' => ':module',
            'controller' => ':controller',
            'action' => ':action',
            'args' => ':id',
        ),
        array(
            'pattern' => '/:module/:controller/:action/',
            'module' => ':module',
            'controller' => ':controller',
            'action' => ':action',
        ),
        array(
            'pattern' => '/:controller/:action/:id',
            'module' => 'app',
            'controller' => ':controller',
            'action' => ':action',
            'args' => ':id',
        ),
        array(
            'pattern' => '/:module/:controller/',
            'module' => ':module',
            'controller' => ':controller',
            'action' => 'index',
        ),
        array(
            'pattern' => '/:controller/:action',
            'module' => 'app',
            'controller' => ':controller',
            'action' => ':action',
        ),
        array(
            'pattern' => '/:module/',
            'module' => ':module',
            'controller' => 'index',
            'action' => 'index',
        ),
        array(
            'pattern' => '/:controller',
            'module' => 'app',
            'controller' => ':controller',
            'action' => 'index',
        ),
        array(
            'pattern' => '/',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'index',
        )
    );

    /**
     * Object constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        Event::fire('framework.router.construct.before');

        $this->_createRoutes(self::$_defaultRoutes);

        Event::fire('framework.router.construct.after', array($this));
        
        $this->_findRoute($this->_url);
    }

    /**
     * 
     * @param string $method
     * @return \THCFrame\Router\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Create routes
     */
    private function _createRoutes(array $routes)
    {
        foreach ($routes as $route) {
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

            if (isset($route['args']) && is_array($route['args'])) {
                foreach ($route['args'] as $arg) {
                    if (preg_match('/^:/', $arg)) {
                        $new_route->addDynamicElement($arg, $arg);
                    }
                }
            } elseif (isset($route['args']) && !is_array($route['args'])) {
                if (preg_match('/^:/', $route['args'])) {
                    $new_route->addDynamicElement($route['args'], $route['args']);
                }
            }

            $this->addRoute($new_route);
        }
    }

    /**
     * Finds a maching route in the routes array using specified $path
     * 
     * @param string $path
     */
    private function _findRoute($path)
    {
        Event::fire('framework.router.findroute.before', array($path));

        foreach ($this->_routes as $route) {
            if (TRUE === $route->matchMap($path)) {
                $this->_lastRoute = $route;
                break;
            }
        }

        Event::fire('framework.router.findroute.after', array(
            $path,
            $this->_lastRoute->getModule(),
            $this->_lastRoute->getController(),
            $this->_lastRoute->getAction())
        );
    }

    /**
     * Add route to route collection
     * 
     * @param \THCFrame\Router\Route $route
     * @return \THCFrame\Router\Router
     */
    public function addRoute(\THCFrame\Router\Route $route)
    {
        array_unshift($this->_routes, $route);
        //$this->_routes[] = $route;
        return $this;
    }

    /**
     * Remove route from route collection
     * 
     * @param \THCFrame\Router\Route $route
     * @return \THCFrame\Router\Router
     */
    public function removeRoute(\THCFrame\Router\Route $route)
    {
        foreach ($this->_routes as $i => $stored) {
            if ($stored == $route) {
                unset($this->_routes[$i]);
            }
        }
        return $this;
    }

    /**
     * Return list of all routes in collection
     * 
     * @return array $list
     */
    public function getRoutes()
    {
        $list = array();

        foreach ($this->_routes as $route) {
            $list[$route->pattern] = get_class($route);
        }

        return $list;
    }

    /**
     * Public method for _createRoutes method
     * 
     * @param array $routes
     */
    public function createRoutes(array $routes)
    {
        $this->_createRoutes($routes);
    }

}
