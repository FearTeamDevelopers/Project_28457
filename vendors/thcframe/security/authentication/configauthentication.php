<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Core\Base;
use THCFrame\Security\Authentication\AuthenticationInterface;

/**
 * Description of configauthentication
 *
 * @author Tomy
 */
class ConfigAuthentication extends Base implements AuthenticationInterface
{

    /**
     * @read
     * @var type 
     */
    protected $_type = 'config';
    protected $_users = array();
    
    private $_securityContext;

    /**
     * 
     */
    private function normalizeUsers()
    {
        $normalizedUsers = array();
        
        foreach ($this->_users as $user) {
            list($username, $hash, $role) = explode(':', $user);
            $newUser = new \App_Model_User(array(
                'active' => true,
                'email' => trim($username),
                'username' => trim($username),
                'password' => trim($hash),
                'role' => trim($role)
            ));
            
            $normalizedUsers[trim($username)] = $newUser;
        }
        
        $this->_users = $normalizedUsers;
    }
    
    /**
     * 
     * @param type $users
     * @param type $securityContext
     */
    public function __construct($users, $securityContext)
    {
        parent::__construct();
        
        $this->_users = $users;
        $this->_securityContext = $securityContext;
        
        $this->normalizeUsers();
    }

    /**
     * 
     * @param type $name
     * @param type $pass
     */
    public function authenticate($name, $pass)
    {
        $errMessage = 'Username and/or password are incorrect';
        
        if(!array_key_exists($name, $this->_users)){
            throw new Exception($errMessage);
        }else{
            $user = $this->_users[$name];
            
            $hash = $this->_securityContext->getSaltedHash($pass);
            
            if($user->getPassword() === $hash){
                $this->_securityContext->setUser($user);
                return true;
            }else{
                throw new Exception($errMessage);
            }
        }
    }

}
