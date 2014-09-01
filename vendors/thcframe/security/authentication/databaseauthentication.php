<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Core\Base;
use THCFrame\Security\Authentication\AuthenticationInterface;
use THCFrame\Security\Exception;
use THCFrame\Security\UserInterface;

/**
 * Description of DatabaseAuthentication
 *
 * @author Tomy
 */
class DatabaseAuthentication extends Base implements AuthenticationInterface
{

    /**
     * @read
     * @var type 
     */
    protected $_type = 'database';
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_name;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_pass;
    
    private $_securityContext;

    public function __construct($options = array(), $securityContext)
    {
        parent::__construct($options);
        $this->_securityContext = $securityContext;
    }

    /**
     * 
     * @param \App_Model_User $user
     * @param type $counter
     */
    private function accountLockdown(UserInterface $user, $counter)
    {
        $counter++;
        $user->loginAttempCounter = $counter;

        if ($counter == 6) {
            $user->loginLockdownTime = time();
        }
        $user->save();
    }
    
    /**
     * Main authentication method which is used for user authentication
     * based on two credentials such as username and password. These login
     * credentials are set in configuration file.
     * 
     * @param type $name
     * @param type $pass
     */
    public function authenticate($name, $pass)
    {
        $errMessage = sprintf('%s and/or password are incorrect', ucfirst($this->_name));

        $user = \App_Model_User::first(array(
                    "{$this->_name} = ?" => $name
        ));

        if ($user === null) {
            throw new Exception($errMessage);
        }

        $counter = $user->getLoginAttempCounter();

        if ($counter > 5) {
            $lockdownTime = $user->getLoginLockdownTime();

            if (time() - $lockdownTime > 1800) {
                $user->loginAttempCounter = 0;
                $user->loginLockdownTime = 0;
            } else {
                throw new Exception($errMessage);
            }
        }

        $hash = $this->_securityContext->getSaltedHash($pass, $user->getSalt());

        if ($user->getPassword() === $hash) {
            unset($user->_password);
            unset($user->_salt);

            if ($user instanceof AdvancedUserInterface) {
                if (!$user->isActive()) {
                    $this->accountLockdown($user, $counter);
                    throw new Exception\UserInactive($errMessage);
                } elseif ($user->isExpired()) {
                    $this->accountLockdown($user, $counter);
                    throw new Exception\UserExpired($errMessage);
                } elseif ($user->isPassExpired()) {
                    $this->accountLockdown($user, $counter);
                    throw new Exception\UserPassExpired($errMessage);
                } else {
                    $user->setLastLogin(date('Y-m-d H:i:s'));
                    $user->loginAttempCounter = 0;
                    $user->loginLockdownTime = 0;
                    $user->save();

                    $this->_securityContext->setUser($user);
                    return true;
                }
            } elseif ($user instanceof UserInterface) {
                if (!$user->isActive()) {
                    $this->accountLockdown($user, $counter);
                    throw new Exception\UserInactive($errMessage);
                } else {
                    $user->loginAttempCounter = 0;
                    $user->loginLockdownTime = 0;
                    $user->save();
                    
                    $this->_securityContext->setUser($user);
                    return true;
                }
            } else {
                throw new Exception\Implementation(sprintf('%s is not implementing UserInterface', get_class($user)));
            }
        } else {
            $this->accountLockdown($user, $counter);
            throw new Exception($errMessage);
        }
    }

}
