<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Core\Base;
use THCFrame\Security\Exception;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Security\SecurityInterface;

/**
 * Description of Authentication
 *
 * @author Tomy
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
     * 
     */
    public function initialize(SecurityInterface $security)
    {
        Event::fire('framework.authentication.initialize.before', array($this->type));
        
        $configuration = Registry::get('configuration');
        
        if (!$this->type) {
            if(!empty($configuration->security->authentication)){
                $this->type = $configuration->security->authentication->type;
                $this->options = (array) $configuration->security->authentication->credentials;
            }else{
                throw new \Exception('Error in configuration file');
            }
        }
        
        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Event::fire('framework.authentication.initialize.after', array($this->type));
        
        switch ($this->type){
            case 'database':{
                return new DatabaseAuthentication($this->options, $security);
                break;
            }
            case 'config':{
                $users = (array) $configuration->security->authentication->users;
                return new ConfigAuthentication($users, $security);
                break;
            }
            default:{
                throw new Exception\Argument('Invalid type');
                break;
            }
        }
    }

}
