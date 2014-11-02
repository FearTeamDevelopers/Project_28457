<?php

namespace THCFrame\Security\Model;

use THCFrame\Model\Model;
use THCFrame\Security\PasswordManager;
use THCFrame\Security\Exception;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\Model\Authtoken;
use THCFrame\Core\Rand;

/**
 * Description of user
 *
 * @author Tomy
 */
class BasicUser extends Model
{

    /**
     * Time after which a password must expire i.e. the password needs to be updated
     * approx 6 months
     * 
     * @var int
     */
    public static $passwordExpiryTime = 15552000;

    /**
     * Maximum time after which the user must re-login
     * approx 1 month
     * 
     * @var int
     */
    public static $rememberMeExpiryTime = 2592000;
    
    /**
     * @readwrite
     */
    protected $_alias = 'us';
    
    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * @index
     * @unique
     *
     * @validate required, email, max(60)
     * @label email address
     */
    protected $_email;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @index
     *
     * @validate required, min(5), max(200)
     * @label password
     */
    protected $_password;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     * 
     * @validate required, alpha, max(25)
     * @label user role
     */
    protected $_role;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     * @unique
     *
     * @validate required, max(40)
     */
    protected $_salt;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric
     * @label last login
     */
    protected $_lastLogin;

    /**
     * @column
     * @readwrite
     * @type integer
     *
     * @validate numeric
     * @label login attempt counter
     */
    protected $_totalLoginAttempts;

    /**
     * @column
     * @readwrite
     * @type integer
     *
     * @validate numeric
     * @label last login attempt
     */
    protected $_lastLoginAttempt;
    
    /**
     * @column
     * @readwrite
     * @type integer
     *
     * @validate numeric
     * @label first login attempt
     */
    protected $_firstLoginAttempt;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;
    
    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary["raw"];

        if (empty($this->$raw)) {
            $this->setCreated(date("Y-m-d H:i:s"));
            $this->setActive(true);
            $this->setLastLogin(0);
            $this->setTotalLoginAttempts(0);
            $this->setLastLoginAttempt(0);
            $this->setFirstLoginAttempt(0);
        }
        
        $this->setModified(date("Y-m-d H:i:s"));
    }
    
    /**
     * 
     * @param type $value
     * @throws \THCFrame\Security\Exception\Role
     */
    public function setRole($value)
    {
        $role = strtolower(substr($value, 0, 5));
        if ($role != 'role_') {
            throw new Exception\Role(sprintf('Role %s is not valid', $value));
        } else {
            $this->_role = $value;
        }
    }
    
    /**
     * Set user last login
     */
    public function setLastLogin($time = null)
    {
        if($time === null){
            $this->_lastLogin = time();
        }else{
            $this->_lastLogin = $time;
        }
    }
    
    /**
     * Function to activate the account
     */
    public function activateAccount()
    {
        $this->_active = true;
        
        if($this->validate()){
            $this->save();
            
            return true;
        }else{
            return false;
        }
    }

    /**
     * Function to deactivate the account
     */
    public function deactivateAccount()
    {
        $this->_active = false;
        
        if($this->validate()){
            $this->save();
            
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Function to check if the user's account is active or not
     * 
     * @return boolean
     */
    public function isActive()
    {
        return (boolean)$this->_active;
    }
    
    /**
     * Function to reset the password for the current user
     * 
     * @param type $oldPassword
     * @param type $newPassword
     * @return boolean
     * @throws Exception\WrongPassword
     */
    public function resetPassword($oldPassword, $newPassword)
    {
        if (!PasswordManager::_validatePassword($oldPassword, $this->getPassword(), $this->getSalt())){
            throw new Exception\WrongPassword('Wrong Password provided');
        }

        $this->salt = PasswordManager::createSalt();
        $this->password = PasswordManager::_hashPassword($newPassword, $this->getSalt);
        
        if($this->validate()){
            $this->save();
            return true;
        }else{
            return false;
        }
    }

    /**
     * Force password reset for user
     * 
     * @param type $newPassword
     * @return boolean
     */
    public function forceResetPassword($newPassword)
    {
        $this->salt = PasswordManager::createSalt();
        $this->password = PasswordManager::_hashPassword($newPassword, $this->getSalt);
        
        if($this->validate()){
            $this->save();
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Function to enable "Remember Me" functionality
     * 
     * @param type $userID
     * @param type $secure
     * @param type $httpOnly
     * @return boolean
     */
    public static function enableRememberMe($userID, $secure = TRUE, $httpOnly = TRUE)
    {
        $authID = Rand::randStr(128);

        $token = new Authtoken(array(
            'userId' => $userID,
            'token' => $authID
        ));

        if ($token->validate()) {
            $token->save();

            if ($secure && $httpOnly) {
                \setcookie('AUTHID', $authID, time() + static::$rememberMeExpiryTime, null, null, TRUE, TRUE);
            } elseif (!$secure && !$httpOnly) {
                \setcookie('AUTHID', $authID, time() + static::$rememberMeExpiryTime, null, null, FALSE, FALSE);
            } elseif ($secure && !$httpOnly) {
                \setcookie('AUTHID', $authID, time() + static::$rememberMeExpiryTime, null, null, TRUE, FALSE);
            } elseif (!$secure && $httpOnly) {
                \setcookie('AUTHID', $authID, time() + static::$rememberMeExpiryTime, null, null, FALSE, TRUE);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Function to check for AUTH token validity
     * 
     * @return boolean
     */
    public static function checkRememberMe()
    {
        if (RequestMethods::cookie('AUTHID') != '') {
            $token = Authtoken::first(array('token = ?' => RequestMethods::cookie('AUTHID')));

            if ($token !== null) {
                $currentTime = time();

                //If cookie time has expired, then delete the cookie from the DB and the user's browser.
                if (($currentTime - $token->created) >= static::$rememberMeExpiryTime) {
                    static::deleteAuthenticationToken();
                    return false;
                } else {
                    //The AUTH token is correct and valid. Hence, return the userID related to this AUTH token
                    return $token->userId;
                }
            } else {
                //If this AUTH token is not found in DB, then erase the cookie from the client's machine and return FALSE
                \setcookie("AUTHID", "");
                return false;
            }
        } else {
            //If the user is unable to provide a AUTH token, then return FALSE
            return false;
        }
    }

    /**
     * Function to delete the current user authentication token from the DB and user cookies
     */
    public static function deleteAuthenticationToken()
    {
        if (RequestMethods::cookie('AUTHID') != '') {
            Authtoken::deleteAll(array('token = ?' => RequestMethods::cookie('AUTHID')));
            \setcookie('AUTHID', '');
        }
    }

}
