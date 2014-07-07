<?php

namespace THCFrame\Security;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Security\Exception as Exception;
use THCFrame\Security\UserInterface;
use THCFrame\Security\AdvancedUserInterface;
use THCFrame\Security\RoleManager;

/**
 * Description of Security
 *
 * @author Tomy
 */
class Security extends Base
{

    /**
     * @read
     * @var type 
     */
    protected $_accessControl;

    /**
     * @read
     * @var type 
     */
    protected $_passwordEncoder;

    /**
     * @read
     * @var type 
     */
    protected $_roleManager;

    /**
     * @read
     * @var type
     */
    protected $_acl;

    /**
     * @read
     * @var type
     */
    protected $_csrfToken;

    /**
     * @read
     * @var type 
     */
    protected $_loginCredentials = array();

    /**
     * @read
     * @var type 
     */
    protected $_user = null;

    /**
     * @read
     * @var type 
     */
    protected $_secret;

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
     * Method creates token as a protection from cross-site request forgery.
     * This token has to be placed in hidden field in every form. Value from
     * form has to be same as value stored in session.
     */
    private function createCsrfToken()
    {
        $session = Registry::get('session');
        $token = $session->get('csrftoken');

        if ($token === null) {
            $this->_csrfToken = bin2hex(openssl_random_pseudo_bytes(15));
            $session->set('csrftoken', $this->_csrfToken);
        } else {
            $this->_csrfToken = $token;
        }
    }

    /**
     * Method initialize security context. Check session for user token and creates
     * role structure or acl object.
     */
    public function initialize()
    {
        Events::fire('framework.security.initialize.before', array($this->accessControll));

        $configuration = Registry::get('config');

        if (!empty($configuration->security)) {
            $rolesOptions = (array) $configuration->security->roles;
            $this->_loginCredentials = $configuration->security->loginCredentials;
            $this->_passwordEncoder = $configuration->security->encoder;
            $this->_accessControl = $configuration->security->accessControl;
            $this->_secret = $configuration->security->secret;
            $this->_twoFactorAuth = (boolean) $configuration->security->twoFactorAuth;
        } else {
            throw new \Exception('Error in configuration file');
        }

        $session = Registry::get('session');
        $user = $session->get('authUser');

        $this->createCsrfToken();

        if ($this->_accessControl == 'role_based') {
            $this->_roleManager = new RoleManager($rolesOptions);
        } elseif ($this->_accessControl == 'acl') {
//            if ($user) {
//                $this->_acl = new Acl($user);
//            }
        } else {
            throw new Exception\Implementation('Access controll is not supported');
        }

        if ($user) {
            $this->_user = $user;
            Events::fire('framework.security.initialize.user', array($user));
        }

        Events::fire('framework.security.initialize.after', array($this->accessControll));

        return $this;
    }

    /**
     * 
     * @param \THCFrame\Security\UserInterface $user
     */
    public function setUser(UserInterface $user)
    {
        @session_regenerate_id();

        $session = Registry::get('session');
        $session->set('authUser', $user)
                ->set('lastActive', time());

        $this->_user = $user;
    }

    /**
     * Method returns user object of logged user
     * 
     * @return \THCFrame\Security\UserInterface
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Method returns actual csrf token
     * 
     * @return string
     */
    public function getCsrfToken()
    {
        return base64_encode($this->_csrfToken);
    }

    /**
     * Method erases all authentication tokens for logged user and regenerates
     * session
     */
    public function logout()
    {
        $session = Registry::get('session');
        $session->erase('authUser')
                ->erase('lastActive');

        $this->_user = NULL;
        @session_regenerate_id();
    }

    /**
     * Method generates 40-chars lenght salt for salting passwords
     * 
     * @return string
     */
    public function createSalt()
    {
        $newSalt = substr(rtrim(base64_encode(md5(microtime())), "="), 3, 40);

        $user = \App_Model_User::first(array(
                    "salt = ?" => $newSalt
        ));

        if ($user === null) {
            return $newSalt;
        } else {
            for ($i = 0; $i < 100; $i++) {
                $newSalt = substr(rtrim(base64_encode(md5(microtime())), "="), 3, 40);

                $user = \App_Model_User::first(array(
                            "salt = ?" => $newSalt
                ));

                if($i == 99){
                    throw new Exception('Salt could not be created');
                }
                
                if ($user === null) {
                    return $newSalt;
                } else {
                    continue;
                }
            }
        }
    }

    /**
     * Method returns salted hash of param value. Specific salt can be set as second
     * parameter, if its not secret from configuration file is used
     * 
     * @param type $value
     * @param type $salt
     * @return string
     * @throws Exception\HashAlgorithm
     */
    public function getSaltedHash($value, $salt = null)
    {
        if ($salt === null) {
            $salt = $this->getSecret();
        } else {
            $salt = $this->getSecret() . $salt;
        }

        if ($value == '') {
            return '';
        } else {
            if (in_array($this->passwordEncoder, hash_algos())) {
                return hash_hmac($this->passwordEncoder, $value, $salt);
            } else {
                throw new Exception\HashAlgorithm(sprintf('Hash algorithm %s is not supported', $this->passwordEncoder));
            }
        }
    }

    /**
     * Method checks if logged user has required role
     * 
     * @param type $requiredRole
     * @return boolean
     * @throws Exception\Role
     */
    public function isGranted($requiredRole)
    {
        if ($this->_user) {
            $userRole = strtolower($this->_user->getRole());
        } else {
            $userRole = 'role_guest';
        }

        $requiredRole = strtolower(trim($requiredRole));

        if (substr($requiredRole, 0, 5) != 'role_') {
            throw new Exception\Role(sprintf('Role %s is not valid', $requiredRole));
        } elseif (!$this->_roleManager->roleExist($requiredRole)) {
            throw new Exception\Role(sprintf('Role %s is not deffined', $requiredRole));
        } else {
            $userRoles = $this->_roleManager->getRole($userRole);

            if (NULL !== $userRoles) {
                if (in_array($requiredRole, $userRoles)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception\Role(sprintf('User role %s is not valid role', $userRole));
            }
        }
    }

    /**
     * Main authentication method which is used for user authentication
     * based on two credentials such as username and password. These login
     * credentials are set in configuration file.
     * 
     * @param type $loginCredential
     * @param type $password
     * @return boolean
     * @throws Exception\UserInactive
     * @throws Exception\UserExpired
     * @throws Exception\UserPassExpired
     * @throws Exception\Implementation
     */
    public function authenticate($loginCredential, $password)
    {
        $user = \App_Model_User::first(array(
                    "{$this->_loginCredentials->login} = ?" => $loginCredential
        ));
                    
        if($user === null){
            throw new Exception('Email address and/or password are incorrect');
        }
        
        $hash = $this->getSaltedHash($password, $user->getSalt());

        if ($user->getPassword() === $hash) {
            unset($user->_password);
            unset($user->_salt);

            if ($user instanceof AdvancedUserInterface) {
                if (!$user->isActive()) {
                    $message = 'User account is not active';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserInactive($message);
                } elseif ($user->isExpired()) {
                    $message = 'User account has expired';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserExpired($message);
                } elseif ($user->isPassExpired()) {
                    $message = 'User password has expired';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserPassExpired($message);
                } else {
                    $user->setLastLogin(date('Y-m-d H:i:s'));
                    $user->save();

                    $this->setUser($user);
                    return true;
                }
            } elseif ($user instanceof UserInterface) {
                if (!$user->isActive()) {
                    $message = 'User account is not active';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserInactive($message);
                } else {
                    $this->setUser($user);
                    return true;
                }
            } else {
                throw new Exception\Implementation(sprintf('%s is not implementing UserInterface', get_class($user)));
            }
        } else {
            return false;
        }
    }

    /**
     * Method creates new salt and salted password and 
     * returns new hash with salt as string.
     * Method can be used only in development environment
     * 
     * @param string $string
     * @return string|null
     */
    public function devGetPasswordHash($string)
    {
        if (ENV == 'dev') {
            $salt = $this->createSalt();
            return $this->getSaltedHash($string, $salt) . '/' . $salt;
        } else {
            return null;
        }
    }

}
