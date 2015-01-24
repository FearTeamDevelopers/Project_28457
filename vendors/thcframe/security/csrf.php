<?php

namespace THCFrame\Security;

use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\Rand;

/**
 * Cross-site Request Forgery protection
 */
class CSRF
{

    /**
     * Token name
     * 
     * @var string 
     */
    protected static $_tokenname = 'csrf';
    
    /**
     * Session object
     * 
     * @var THCFrame\Session\Session 
     */
    private $_session;

    /**
     * Generates a new token value and saves it in session
     */
    private function setToken()
    {
        if ($this->getToken() === false) {
            $token = Rand::randStr(32);
            $this->writeTokenToSession($token);
        }
    }

    /**
     * Writes token to session
     */
    private function writeTokenToSession($token)
    {
        $this->_session->set(self::$_tokenname, $token);
    }

    /**
     * Refresh token stored in session
     */
    private function refreshToken()
    {
        $this->_session->erase(self::$_tokenname);

        $this->setToken();
    }
    
    /**
     * 
     * @param string $tokenname
     */
    public function __construct($tokenname = 'csrf')
    {
        self::$_tokenname = $tokenname;
        
        $this->_session = Registry::get('session');
        $this->setToken();
    }

    /**
     * Verify if supplied token matches the stored token
     *
     * @param string $token
     * @return boolean
     */
    public function isValidToken($token)
    {
        return ($token === $this->getToken());
    }

    /**
     * Generates the HTML input field with the token
     */
    public function generateHiddenField()
    {
        $token = $this->getToken();
        echo '<input type="hidden" name="' . self::$_tokenname . '" value="' . $token . '" />';
    }

    /**
     * Verifies whether the post token was set, else dies with error
     * 
     * @return boolean
     */
    public function verifyRequest()
    {
        $checkPost = RequestMethods::issetpost(self::$_tokenname) && $this->isValidToken(RequestMethods::post(self::$_tokenname));
        $checkGet = RequestMethods::issetget(self::$_tokenname) && $this->isValidToken(RequestMethods::get(self::$_tokenname));
        $this->refreshToken();

        if ($checkGet || $checkPost) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reads token from session
     * 
     * @return string
     */
    public function getToken()
    {
        if ($this->_session->get(self::$_tokenname) !== null) {
            return $this->_session->get(self::$_tokenname);
        } else {
            return false;
        }
    }
    
    /**
     * Return tokenname
     * 
     * @return string
     */
    public function getTokenname()
    {
        return self::$_tokenname;
    }
    

}
