<?php

namespace THCFrame\Security;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Security\Exception;
use THCFrame\Security\UserInterface;
use THCFrame\Security\SecurityInterface;

/**
 * Description of Security
 *
 * @author Tomy
 */
class Security extends Base implements SecurityInterface
{

    /**
     * @read
     * @var type 
     */
    protected $_authentication;

    /**
     * @read
     * @var type 
     */
    protected $_authorization;

    /**
     * @read
     * @var type 
     */
    protected $_passwordEncoder;

    /**
     * @read
     * @var type
     */
    protected $_userToken;

    /**
     * @read
     * @var type
     */
    protected $_csrfToken;

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
    public function createCsrfToken()
    {
        $session = Registry::get('session');
        $token = $session->get('csrftoken');

        if ($token === null) {
            $this->_csrfToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(15)));
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
        Event::fire('framework.security.initialize.before', array($this->accessControll));

        $configuration = Registry::get('configuration');

        if (!empty($configuration->security)) {
            $this->_passwordEncoder = $configuration->security->encoder;
            $this->_secret = $configuration->security->secret;
        } else {
            throw new \Exception('Error in configuration file');
        }

        $session = Registry::get('session');
        $user = $session->get('authUser');

        $this->createCsrfToken();

        $authentication = new Authentication\Authentication();
        $this->_authentication = $authentication->initialize($this);

        $authorization = new Authorization\Authorization();
        $this->_authorization = $authorization->initialize();

        if ($user instanceof UserInterface) {
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

        Event::fire('framework.security.initialize.after', array($this->accessControll));

        return $this;
    }

    /**
     * 
     * @param type $postToken
     */
    public function checkCsrfToken($postToken)
    {
        $session = Registry::get('session');
        $originalToken = $session->get('csrftoken');

        if (base64_decode($postToken) === base64_decode($originalToken)) {
            return true;
        } else {
            return false;
        }
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
     * Method erases all authentication tokens for logged user and regenerates
     * session
     */
    public function logout()
    {
        $session = Registry::get('session');
        $session->clear();

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

                if ($i == 99) {
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
     * 
     * @param type $name
     * @param type $pass
     * @return type
     */
    public function authenticate($name, $pass)
    {
        try {
            return $this->_authentication->authenticate($name, $pass);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * @param type $requiredRole
     * @return type
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
     * 
     * @param type $text
     * @return type
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
     * 
     * @param type $encryptedText
     */
    public function decrypt($encryptedText)
    {
        $key = pack('H*', '0df9cf7ce4fbde15dc3e9303da18208e485ea44797a2795b239dda8e546845d4');
        $ciphertext_dec = base64_decode($encryptedText);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);

        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);

        echo $plaintext_dec . "\n";
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
