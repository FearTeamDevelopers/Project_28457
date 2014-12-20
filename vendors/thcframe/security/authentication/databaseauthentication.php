<?php

namespace THCFrame\Security\Authentication;

use THCFrame\Security\Authentication\Authentication;
use THCFrame\Security\Authentication\AuthenticationInterface;
use THCFrame\Security\Exception;
use THCFrame\Security\Model\BasicUser;
use THCFrame\Security\Model\AdvancedUser;
use THCFrame\Security\PasswordManager;
use THCFrame\Core\Core;

/**
 * DatabaseAuthentication verify user identity against database records
 */
class DatabaseAuthentication extends Authentication implements AuthenticationInterface
{

    /**
     * First credential used for authentication
     * 
     * @readwrite
     * @var string   
     */
    protected $_name = 'email';

    /**
     * Second credential used for authentication
     * 
     * @readwrite
     * @var string 
     */
    protected $_pass = 'password';

    /**
     * @readwrite
     * @var boolean 
     */
    protected $_bruteForceDetection = true;
    
    /**
     * It denotes the # of maximum attempts for login using the password. 
     * If this limit exceeds and this happens within a very short amount 
     * of time (which is defined by $bruteForceLockAttemptTotalTime), 
     * then it is considered as a brute force attack.
     * 
     * @readwrite
     * @var int
     */
    protected $_bruteForceLockAttempts = 5;

    /**
     * It denotes the amount of time in seconds between which no two wrong 
     * passwords must be entered. If this happens, then it is considered 
     * that a bot is trying to hack the account using brute-force.
     * 
     * 1 SEC  - This defines the time-period after which next login attempt 
     * must be carried out. E.g if the time is 1 sec, then time-period between 
     * two login attempts must minimum be 1 sec. Assuming that user will take 
     * atleast 1 sec time to type between two passwords.
     * 
     * @readwrite
     * @var int
     */
    protected $_bruteForceLockTimePeriod = 1;

    /**
     * It denotes the amount of time in seconds within which total number of 
     * attempts ($bruteForceLockAttempts) must not exceed its maximum value. 
     * If this happens , then it is considered as a brute force attack.
     * 
     * This tells that if ($bruteForceLockAttempts) login attempts are made 
     * within ($bruteForceLockAttemptTotalTime) time then it will be a brute force.
     * 
     * @readwrite
     * @var int
     */
    protected $_bruteForceLockAttemptTotalTime = 25;

    /**
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        $this->name = $options['credentials']->name;
        $this->pass = $options['credentials']->pass;
        
    }

    /**
     * Main authentication method which is used for user authentication
     * based on two credentials such as username and password. These login
     * credentials are set in database.
     * 
     * @param string $name  Username or email
     * @param string $pass  Password
     */
    public function authenticate($name, $pass)
    {
        $errMessage = sprintf('%s and/or password are incorrect', ucfirst($this->_name));

        $user = \App_Model_User::first(array(
                    "{$this->_name} = ?" => $name
        ));

        if ($user === null) {
            throw new Exception\UserNotExists($errMessage);
        }
        
        $passVerify = PasswordManager::validatePassword($pass, $user->getPassword(), $user->getSalt());
        
        if ($passVerify === true) {
            if ($user instanceof AdvancedUser) {
                if (!$user->isActive()) {
                    throw new Exception\UserInactive($errMessage);
                } elseif ($user->isAccountExpired()) {
                    throw new Exception\UserExpired($errMessage);
                } elseif ($user->isPasswordExpired()) {
                    throw new Exception\UserPassExpired($errMessage);
                } else {
                    $user->setLastLogin();
                    $user->setTotalLoginAttempts(0);
                    $user->setLastLoginAttempt(0);
                    $user->setFirstLoginAttempt(0);
                    $user->save();

                    $user->password = null;
                    $user->salt = null;
                    
                    return $user;
                }
            } elseif ($user instanceof BasicUser) {
                if (!$user->isActive()) {
                    throw new Exception\UserInactive($errMessage);
                } else {
                    $user->setLastLogin();
                    $user->setTotalLoginAttempts(0);
                    $user->setLastLoginAttempt(0);
                    $user->setFirstLoginAttempt(0);
                    $user->save();
                    
                    $user->password = null;
                    $user->salt = null;
                    
                    return $user;
                }
            } else {
                throw new Exception\Implementation(sprintf('%s is not implementing BasicUser', get_class($user)));
            }
        } else {
            if ($this->_bruteForceDetection === true) {
                if ($this->isBruteForce($user)) {
                    $identifier = $this->_name;
                    Core::getLogger()->log(sprintf('Brute Force Attack Detected for account %s', $user->$identifier));
                    
                    throw new Exception\BruteForceAttack('WARNING: Brute Force Attack Detected. We Recommend you use captcha.');
                }else{
                    throw new Exception\WrongPassword($errMessage);
                }
            } else {
                throw new Exception\WrongPassword($errMessage);
            }
        }
    }

    /**
     * Function to detect brute-force attacks.
     * 
     * @param string $user    User object
     * @return boolean      Returns True if brute-force is detected. False otherwise
     */
    protected function isBruteForce($user)
    {
        $currentTime = time();

        //if firstLoginAttempt OR lastLoginAttempt are not set, then set them and return false.
        if (($user->getFirstLoginAttempt() == 0) || ($user->getLastLoginAttempt() == 0)) {
            $user->setTotalLoginAttempts($user->getTotalLoginAttempts() + 1);
            $user->setLastLoginAttempt($currentTime);
            $user->setFirstLoginAttempt($currentTime);
            $user->save();

            return false;
        }

        //if two failed login attempts are made within $_bruteForceLockTimePeriod 
        //time period, then reset the counters and return true to declare this a brute force attack.
        if (($currentTime - $user->getLastLoginAttempt()) <= $this->bruteForceLockTimePeriod) {
            $user->setTotalLoginAttempts(0);
            $user->setLastLoginAttempt(0);
            $user->setFirstLoginAttempt(0);
            $user->save();

            return true;
        }

        //check if two subsequent requests are made within $_bruteForceLockAttemptTotalTime time-period.
        if (($currentTime - $user->getFirstLoginAttempt()) <= $this->bruteForceLockAttemptTotalTime) {
            // To check how many total failed attempts have happened. 
            // If more than $_bruteForceLockAttempts attempts have happened, 
            // then that is an attack. Hence we reset the counters and return TRUE.
            if ($user->getTotalLoginAttempts() >= $this->bruteForceLockAttempts) {
                $user->setTotalLoginAttempts(0);
                $user->setLastLoginAttempt(0);
                $user->setFirstLoginAttempt(0);
                $user->save();

                return true;
            } else {
                //since the total login attempts have not crossed $_bruteForceLockAttempts, 
                //this is not a brute force attack. Hence we just update our counters.
                $user->setTotalLoginAttempts($user->getTotalLoginAttempts() + 1);
                $user->setLastLoginAttempt($currentTime);
                $user->save();

                return false;
            }
        } else {
            //since difference between two failed login requests are out of 
            //$_bruteForceLockAttemptTotalTime time period, we can safely reset 
            //all the counters and TELL THAT THIS IS NOT A BRUTE FORCE ATTACK.
            $user->setTotalLoginAttempts(0);
            $user->setLastLoginAttempt(0);
            $user->setFirstLoginAttempt(0);
            $user->save();

            return false;
        }
    }

}
