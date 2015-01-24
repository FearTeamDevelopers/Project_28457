<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Core\Base;
use THCFrame\Security\Exception;
use THCFrame\Events\Events as Event;

/**
 * Authentication factory class
 */
class Authentication extends Base
{

    /**
     * @readwrite
     * @var type 
     */
    protected $_type;
    
    /**
     * @readwrite
     * @var type 
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
    public function initialize($configuration)
    {
        Event::fire('framework.authentication.initialize.before', array($this->type));
        
        if (!$this->type) {
            if(!empty($configuration->security->authentication)){
                $this->type = $configuration->security->authentication->type;
                $this->options = (array) $configuration->security->authentication;
            }else{
                throw new \Exception('Error in configuration file');
            }
        }
        
        if (!$this->type) {
            throw new Exception\Argument('Invalid authentication type');
        }

        Event::fire('framework.authentication.initialize.after', array($this->type));
        
        switch ($this->type){
            case 'database':{
                return new DatabaseAuthentication($this->options);
                break;
            }
            case 'config':{
                $users = (array) $configuration->security->authentication->users;
                return new ConfigAuthentication($users);
                break;
            }
            default:{
                throw new Exception\Argument('Invalid authentication type');
                break;
            }
        }
    }

}
