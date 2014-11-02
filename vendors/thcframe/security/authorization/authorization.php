<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Exception;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * Authorization factory class
 */
class Authorization extends Base
{
    
    /**
     * Authorization type
     * 
     * @readwrite
     * @var string
     */
    protected $_type;
    
    /**
     * @readwrite
     * @var array
     */
    protected $_options;
    
    /**
     * 
     * @param type $method
     * @return \THCFrame\Security\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }
    
    /**
     * Factory method
     * It accepts initialization options and selects the type of returned object, 
     * based on the internal $_type property
     */
    public function initialize()
    {
        Event::fire('framework.authorization.initialize.before', array($this->type));
        
        $configuration = Registry::get('configuration');
        
        if (!$this->type) {
            if(!empty($configuration->security->authorization)){
                $this->type = $configuration->security->authorization->type;
                $this->options = (array) $configuration->security->authorization;
                
                $roles = (array) $configuration->security->authorization->roles;
                $roleManager = new RoleManager($roles);
            }else{
                throw new \Exception('Error in configuration file');
            }
        }
        
        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }
        
        Event::fire('framework.authorization.initialize.after', array($this->type));
        
        switch ($this->type){
            case 'annotationbase':{
                return new AnnotationBaseAuthorization($roleManager);
                break;
            }
            case 'resourcebase':{
                $resources = (array) $configuration->security->authorization->resources;
                return new ResourceBaseAuthorization($roleManager, $resources);
                break;
            }
            default:{
                throw new Exception\Argument('Invalid type');
                break;
            }
        }
    }
}
