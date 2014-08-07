<?php

namespace THCFrame\Router;

use THCFrame\Core\Base as Base;
use THCFrame\Router\Exception as Exception;

/**
 * Description of Route
 * Router\Route class inherits from the Base class, so we can define all manner of simulated
 * getters/setters. 
 * All of the protected properties relate to the variables provided when a 
 * new Router\Route (or subclass) instance are created, and contain information about the URL requested.
 *
 * @author Tomy
 */
class Route extends Base
{

    /**
     * The Route path consisting of route elements
     * @var string
     * @readwrite
     */
    protected $_pattern;

    /**
     *
     * @var type 
     * @readwrite
     */
    protected $_module;

    /**
     * The name of the class that this route maps to
     * @var string
     * @readwrite
     */
    protected $_controller;

    /**
     * The name of the class method that this route maps to
     * @var string
     * @readwrite
     */
    protected $_action;

    /**
     * 
     * @param string $method
     * @return \THCFrame\Router\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

}