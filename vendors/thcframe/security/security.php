<?php

namespace THCFrame\Security;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Security\Exception;
use THCFrame\Security\SecurityInterface;
use THCFrame\Security\CSRF;
use THCFrame\Security\PasswordManager;
use THCFrame\Security\Model\BasicUser;

/**
 * Security context class. Wrapper for authentication and authorization methods
 */
class Security extends Base implements SecurityInterface
{

    /**
     * Authentication object
     * 
     * @read
     * @var THCFrame\Security\Authentication\Authentication 
     */
    protected $_authentication;

    /**
     * Authorization object
     * 
     * @read
     * @var THCFrame\Security\Authorization\Authorization 
     */
    protected $_authorization;

    /**
     * Cross-site request forgery protection
     * 
     * @read
     * @var THCFrame\Security\CSRF
     */
    protected $_csrf;

    /**
     * PasswordManager object
     * 
     * @read
     * @var THCFrame\Security\PasswordManager 
     */
    protected $_passwordManager;

    /**
     * Authenticated user object
     * @readwrite
     * @var \THCFrame\Security\Model\BasicUser or null
     */
    protected $_user = null;

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
     * Method initialize security context. Check session for user token and
     * initialize authentication and authorization classes
     */
    public function initialize($configuration)
    {
        Event::fire('framework.security.initialize.before', array());

        if (!empty($configuration->security)) {
            $this->_csrf = new CSRF();
            $this->_passwordManager = new PasswordManager($configuration->security);
        } else {
            throw new \Exception('Error in configuration file');
        }

        $session = Registry::get('session');
        $user = $session->get('authUser');

        $authentication = new Authentication\Authentication();
        $this->_authentication = $authentication->initialize($configuration);

        $authorization = new Authorization\Authorization();
        $this->_authorization = $authorization->initialize($configuration);

        if ($user instanceof BasicUser) {
            $this->_user = $user;
            Event::fire('framework.security.initialize.user', array($user));
        }

        if ($this->_authorization->type == 'resourcebase') {
            Event::add('framework.router.findroute.after', function($path) {
                $role = $this->getAuthorization()->checkForResource($path);

                if ($role !== null) {
                    if ($this->isGranted($role) !== true) {
                        throw new \THCFrame\Security\Exception\Unauthorized();
                    }
                }
            });
        }

        Event::fire('framework.security.initialize.after', array());

        return $this;
    }

    /**
     * 
     * @param BasicUser $user
     * @return type
     */
    public function setUser(BasicUser $user)
    {
        @session_regenerate_id();

        $session = Registry::get('session');
        $session->set('authUser', $user)
                ->set('lastActive', time());

        $this->_user = $user;
        return;
    }

    /**
     * 
     * @return type
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Return Cross-site request forgery object
     * 
     * @return THCFrame\Security\CSRF
     */
    public function getCSRF()
    {
        return $this->_csrf;
    }

    /**
     * Return PasswordManager object
     * 
     * @return THCFrame\Security\PasswordManager
     */
    public function getPasswordManager()
    {
        return $this->_passwordManager;
    }

    /**
     * Method erases all authentication tokens for logged user and regenerates
     * session
     */
    public function logout()
    {
        $session = Registry::get('session');
        $session->erase('authUser')
                ->erase('lastActive')
                ->erase('csrf');
        
        BasicUser::deleteAuthenticationToken();

        $this->_user = NULL;
        @session_regenerate_id();
    }

    /**
     * Authentication facade method
     * 
     * @param string $name
     * @param string $pass
     * @return mixed
     */
    public function authenticate($name, $pass)
    {
        try {
            $user = $this->_authentication->authenticate($name, $pass);
            $this->setUser($user);
            return true;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Authorization facade method
     * 
     * @param string $requiredRole
     * @return mixed
     */
    public function isGranted($requiredRole)
    {
        try {
            return $this->_authorization->isGranted($this->getUser(), $requiredRole);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Encrypt provided text
     * 
     * @param string $text
     * @return string
     */
    public function encrypt($text)
    {
        $key = pack('H*', '0df9cf7ce4fbde15dc3e9303da18208e485ea44797a2795b239dda8e546845d4');
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_CBC, $iv);
        $ciphertext = $iv . $ciphertext;
        $ciphertext_base64 = base64_encode($ciphertext);

        return $ciphertext_base64;
    }

    /**
     * Decrypt encrypted text
     * 
     * @param string $encryptedText
     * @return string
     */
    public function decrypt($encryptedText)
    {
        $key = pack('H*', '0df9cf7ce4fbde15dc3e9303da18208e485ea44797a2795b239dda8e546845d4');
        $ciphertext_dec = base64_decode($encryptedText);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);

        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);

        return $plaintext_dec;
    }
    
    /**
     * Function for user to log-in forcefully i.e without providing user-credentials
     * 
     * @param integer $userId
     * @return boolean
     * @throws Exception\UserNotExists
     */
    public function forceLogin($userId)
    {
        $user = \App_Model_User::first(array('id = ?' => (int)$userId));
        
        if($user === null){
            throw new Exception\UserNotExists('User not found');
        }
        
        $this->setUser($user);
        return true;
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
            $salt = $this->getPasswordManager()->createSalt();
            return $this->getPasswordManager()->getPasswordHash($string, $salt) . '/' . $salt;
        } else {
            return null;
        }
    }

}
