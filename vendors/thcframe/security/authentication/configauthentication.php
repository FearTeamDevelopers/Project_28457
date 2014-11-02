<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Core\Base;
use THCFrame\Security\Authentication\AuthenticationInterface;

/**
 * ConfigAuthentication verify user identity against informations stored in
 * config file
 */
class ConfigAuthentication extends Base implements AuthenticationInterface
{

    /**
     * @read
     * @var type 
     */
    protected $_type = 'config';
    
    /**
     * Accepted identities from config file
     * 
     * @var array
     */
    protected $_users = array();
    
    /**
     * Create user objects base on informations in config file
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
     * Object constructor
     * 
     * @param array $users
     */
    public function __construct($users = array())
    {
        parent::__construct();
        
        $this->_users = $users;
        
        $this->normalizeUsers();
    }

    /**
     * Main authentication method which is used for user authentication
     * based on two credentials such as username and password. These login
     * credentials are set in configuration file.
     * 
     * @param string $name
     * @param string $pass
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
