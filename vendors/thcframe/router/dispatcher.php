<?php

namespace THCFrame\Router;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Core\Inspector;
use THCFrame\Router\Exception;

/**
 * Description of Dispatcher
 * Dispatcher class will use the requested URL, as well as the controller/action metadata, 
 * to determine the correct controller/action to execute. 
 * It needs to handle multiple defined routes and inferred routes if no defined routes
 * are matched.
 *
 * @author Tomy
 */
final class Dispatcher extends Base
{

    /**
     * Module active for last reques
     * @var type 
     * @read
     */
    protected $_activeModule;

    /**
     * The suffix used to append to the class name
     * @var string
     * @read
     */
    protected $_suffix;

    /**
     * The path to look for classes (or controllers)
     * @var string
     * @read
     */
    protected $_controllerPath;

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
     * Sets a suffix to append to the class name being dispatched
     * 
     * @param string $suffix
     * @return \THCFrame\Router\Dispatcher
     */
    protected function _setSuffix($suffix)
    {
        $this->_suffix = '.' . $suffix;

        return $this;
    }

    /**
     * 
     * @return type
     */
    protected function _getSuffix()
    {
        return $this->_suffix;
    }

    /**
     * Set the path where dispatch class (controllers) reside
     * 
     * @param string $path
     * @return \THCFrame\Router\Dispatcher
     */
    protected function _setControllerPath($path)
    {
        $this->_controllerPath = preg_replace('/\/$/', '', $path) . '/';

        return $this;
    }

    /**
     * 
     */
    public function initialize()
    {
        Event::fire('framework.dispatcher.initialize.before', array());

        $configuration = Registry::get('configuration');

        if (!empty($configuration->dispatcher)) {
            $this->_setSuffix($configuration->dispatcher->suffix);
        } else {
            throw new \Exception('Error in configuration file');
        }

        Event::fire('framework.dispatcher.initialize.after', array());

        return $this;
    }

    /**
     * Attempts to dispatch the supplied Route object. 
     * 
     * @param \THCFrame\Router\Route $route
     * @throws Exception\Module
     * @throws Exception\Controller
     * @throws Exception\Action
     */
    public function dispatch(\THCFrame\Router\Route $route)
    {
        $module = trim($route->getModule());
        $class = trim($route->getController());
        $action = trim($route->getAction());
        $parameters = $route->getMapArguments();

        if ('' === $module) {
            throw new Exception\Module('Module Name not specified');
        } elseif ('' === $class) {
            throw new Exception\Controller('Class Name not specified');
        } elseif ('' === $action) {
            throw new Exception\Action('Method Name not specified');
        }

        if ($module == 'app') {
            $status = $this->loadConfigFromDb('appstatus');

            if ($status !== null && $status != 1) {
                throw new Exception\Offline('Application is offline');
            }
        }

        $module = str_replace('\\', '', $module);
        preg_match('/^[a-zA-Z0-9_]+$/', $module, $matches);
        if (count($matches) !== 1) {
            throw new Exception\Module(sprintf('Disallowed characters in module name %s', $module));
        }

        $class = str_replace('\\', '', $class);
        preg_match('/^[a-zA-Z0-9_]+$/', $class, $matches);
        if (count($matches) !== 1) {
            throw new Exception\Controller(sprintf('Disallowed characters in class name %s', $class));
        }

        $file_name = strtolower("./modules/{$module}/controller/{$class}.php");
        $class = ucfirst($module) . '_Controller_' . ucfirst($class);

        if (FALSE === file_exists($file_name)) {
            throw new Exception\Controller(sprintf('Class file %s not found', $file_name));
        } else {
            require_once($file_name);
        }

        $this->_activeModule = $module;

        Event::fire('framework.dispatcher.controller.before', array($class, $parameters));

        try {
            $instance = new $class(array(
                'parameters' => $parameters
            ));
            Registry::set('controller', $instance);
        } catch (\Exception $e) {
            throw new Exception\Controller(sprintf('Controller %s error: %s', $class, $e->getMessage()));
        }

        Event::fire('framework.dispatcher.controller.after', array($class, $parameters));

        if (!method_exists($instance, $action)) {
            $instance->willRenderLayoutView = false;
            $instance->willRenderActionView = false;

            throw new Exception\Action(sprintf('Action %s not found', $action));
        }

        $inspector = new Inspector($instance);
        $methodMeta = $inspector->getMethodMeta($action);

        if (!empty($methodMeta['@protected']) || !empty($methodMeta['@private'])) {
            throw new Exception\Action(sprintf('Action %s not found', $action));
        }

        $hooks = function($meta, $type) use ($inspector, $instance) {
            if (isset($meta[$type])) {
                $run = array();

                foreach ($meta[$type] as $method) {
                    $hookMeta = $inspector->getMethodMeta($method);

                    if (in_array($method, $run) && !empty($hookMeta['@once'])) {
                        continue;
                    }

                    $instance->$method();
                    $run[] = $method;
                }
            }
        };

        Event::fire('framework.dispatcher.beforehooks.before', array($action, $parameters));

        $hooks($methodMeta, '@before');

        Event::fire('framework.dispatcher.beforehooks.after', array($action, $parameters));
        Event::fire('framework.dispatcher.action.before', array($action, $parameters));
        
        call_user_func_array(array(
            $instance, $action), is_array($parameters) ? $parameters : array());

        Event::fire('framework.dispatcher.action.after', array($action, $parameters));
        Event::fire('framework.dispatcher.afterhooks.before', array($action, $parameters));
        
        $hooks($methodMeta, '@after');

        Event::fire('framework.dispatcher.afterhooks.after', array($action, $parameters));

        // unset controller

        Registry::erase('controller');
    }

}
