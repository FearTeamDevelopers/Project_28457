<?php

namespace THCFrame\Security;

use THCFrame\Core\Base;
use THCFrame\Core\Rand;
use THCFrame\Security\Exception;
use THCFrame\Registry\Registry;

/**
 * OWASP password manager
 */
class PasswordManager extends Base
{

    /**
     * Current hash algorithm in use
     * 
     * @readwrite
     * @var string
     */
    protected $_encoder = 'sha512';

    /**
     * Minimum password strength that all passwords must have
     * 
     * @readwrite
     * @var float
     */
    protected $_passwordStrength = 0.5;

    /**
     * @readwrite
     * @var type 
     */
    protected $_secret;

    /**
     * Set of supported keyboard layouts for password strength detection
     * @var array
     */
    protected static $keyboardSets = array(
        'qwerty' => '1234567890qwertyuiopasdfghjklzxcvbnm',
        'azerty' => '1234567890azertyuiopqsdfghjklmwxcvbn',
        'qwertz' => '1234567890qwertzuiopasdfghjklyxcvbnm',
        'dvorak' => '1234567890pyfgcrlaoeuidhtnsqjkxbmwvz'
    );

    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    /**
     * Static wrapper for hashPassword function
     * 
     * @param string $pass          password in plain-text
     * @param string $dynamicSalt       dynamic salt
     * @param string $algo          The algorithm used to calculate hash
     * @return string           final hash
     */
    public static function hashPassword($pass, $dynamicSalt = '', $algo = '')
    {
        $configuration = Registry::get('configuration');
            
        $pm = new static($configuration->security);
        return $pm->getPasswordHash($pass, $dynamicSalt, $algo);
    }
    
    /**
     * To create hash of a string using dynamic and static salt
     * 
     * @param string $pass          password in plain-text
     * @param string $dynamicSalt       dynamic salt
     * @param string $algo          The algorithm used to calculate hash
     * @return string           final hash
     */
    public function getPasswordHash($pass, $dynamicSalt = '', $algo = '')
    {
        if ($algo == '') {
            $algo = $this->getEncoder();
        }
        
        if ($dynamicSalt == '') {
            $salt = $this->getSecret();
        } else {
            $salt = $this->getSecret() . $dynamicSalt;
        }

        return hash_hmac($algo, $pass, $salt);
    }

    /**
     * Static wrapper for validatePassword function
     * 
     * @param type $newPassword
     * @param type $oldHash
     * @param type $oldSalt
     * @param type $oldAlgo
     */
    public static function validatePassword($newPassword, $oldHash, $oldSalt)
    {
        $configuration = Registry::get('configuration');
            
        $pm = new static($configuration->security);
        return $pm->isPasswordValid($newPassword, $oldHash, $oldSalt);
    }
    
    /**
     * To calculate hash of given password and then to check its equality against the old password's hash
     * 
     * @param string $newPassword       The given password in plain-text
     * @param string $oldHash       The old hash
     * @param string $oldSalt       The old dynamic salt used to create the old hash
     * @param string $oldAlgo       The old algo used to create the hash
     * @return boolean          True if new hash and old hash match. False otherwise
     */
    public function isPasswordValid($newPassword, $oldHash, $oldSalt)
    {
        $newHash = $this->getPasswordHash($newPassword, $oldSalt);

        if ($newHash === $oldHash) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Method generates 40-chars lenght salt for salting passwords
     * 
     * @return string
     */
    public static function createSalt()
    {
        $newSalt = Rand::randStr(40);

        $user = \App_Model_User::first(array(
                    'salt = ?' => $newSalt
        ));

        if ($user === null) {
            return $newSalt;
        } else {
            for ($i = 0; $i < 100; $i++) {
                $newSalt = Rand::randStr(40);

                $user = \App_Model_User::first(array(
                            'salt = ?' => $newSalt
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
     * To calculate entropy of a string
     * 
     * @param string $string    The string whose entropy is to be calculated
     * @return float        The entropy of the string
     */
    public static function entropy($string)
    {
        $h = 0;
        $size = strlen($string);

        //Calculate the occurence of each character and compare that number with 
        //the overall length of the string and put it in the entropy formula
        foreach (count_chars($string, 1) as $v) {
            $p = $v / $size;
            $h -= $p * log($p) / log(2);
        }

        return $h;
    }

    /**
     * To check if the string has ordered characters i.e. characters in strings
     * are consecutive - such as 'abcd'. Also checks for reverse patterns such as 'dcba'
     * 
     * @param string $string    String in which we have to check for presence of ordered characters
     * @param int $length       Minimum length of pattern to be qualified as ordered. e.g. 
     *                          String 'abc' is not ordered if the length is 4 because it 
     *                          takes a minimum of 4 characters in consecutive orders to mark 
     *                          the string as ordered. Thus, the string 'abcd' is an ordered 
     *                          character of length 4. Similarly 'xyz' is ordered character of 
     *                          length 3 and 'uvwxyz' is ordered character of length 6
     * @return boolean      Returns true if ordered characters are found. False otherwise
     */
    public static function hasOrderedCharacters($string, $length)
    {
        $length = (int) $length;

        $i = 0;
        $j = strlen($string);

        //Group all the characters into length 1, and calculate their ASCII value. 
        //If they are continous, then they contain ordered characters.
        $str = implode('', array_map(function($m) use (&$i, &$j) {
                    return chr((ord($m[0]) + $j--) % 256) . chr((ord($m[0]) + $i++) % 256);
                }, str_split($string, 1)));

        return \preg_match('#(.)(.\1){' . ($length - 1) . '}#', $str) == true;
    }

    /**
     * To check if the string has keyboard ordered characters i.e. strings such as 'qwert'. 
     * Also checks for reverse patterns such as 'rewq'.
     * 
     * @param string $string    String in which we have to check for presence of ordered characters
     * @param int $length       Minimum length of pattern to be qualified as ordered. e.g. 
     *                          String 'qwe' is not ordered if the length is 4 because it takes a 
     *                          minimum of 4 characters in consecutive orders to mark the string as ordered. 
     *                          Thus, the string 'qwer' is an ordered character of length 4. 
     *                          Similarly 'asd' is ordered character of length 3 and 'zxcvbn' is ordered character of length 6
     * @return boolean      Returns true if ordered characters are found. False otherwise
     */
    public static function hasKeyboardOrderedCharacters($string, $length)
    {
        $length = (int) $length;

        $i = 0;
        $j = strlen($string);

        //group all the characters into length 1, and calculate their positions. 
        //If the positions match with the value $keyboardSet, then they contain keyboard ordered characters
        foreach (self::$keyboardSets as $set) {
            $str = implode('', array_map(function($m) use (&$i, &$j, $set) {
                        return ((strpos($set, $m[0]) + $j--) ) . ((strpos($set, $m[0]) + $i++) );
                    }, str_split($string, 1)));

            if (\preg_match('#(..)(..\1){' . ($length - 1) . '}#', $str) == true) {
                return true;
            }
        }

        return false;
    }

    /**
     * To check if the string is a phone-number. There are many cases 
     * for a legitimate phone number such as various area codes, 
     * strings in phone numbers, dashes in between numbers, etc. 
     * Hence not all possible combinations were taken into account
     * 
     * @param string $string    The string to be checked
     * @return boolean      Returns true if the string is a phone number. False otherwise
     */
    public static function isPhoneNumber($string)
    {
        //If the string contains only numbers and the length of the string is between 6 and 13, it is possibly a phone number
        //checks for a '+' sign infront of string which may be present. Then checks for digits
        preg_match_all('/^(\+)?\d{6,13}$/i', $string, $matches);

        if (count($matches[0]) >= 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * To check if the string contains a phone-number. There are many cases 
     * for a legitimate phone number such as various area codes, 
     * strings in phone numbers, dashes in between numbers, etc. 
     * Hence not all possible combinations were taken into account.
     * 
     * @param string $string    The string to be checked
     * @return boolean      Returns true if the string contains a phone number. False otherwise
     */
    public static function containsPhoneNumber($string)
    {
        //If the string contains continous numbers of length beteen 6 and 13, 
        //then it is possible that the string contains a phone-number pattern. e.g. owasp+91917817
        //checks for a '+' sign infront of string which may be present. Then checks for digits.
        preg_match_all('/(\+)?\d{6,13}/i', $string, $matches);

        if (count($matches[0]) >= 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * To check if the string is a date.
     * 
     * @param string $string    The string to be checked
     * @return boolean      Returns true if the string is a date. False otherwise
     */
    public static function isDate($string)
    {
        //This checks dates of type Date-Month-Year (all digits)
        preg_match_all('/^(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?(0?[1-9]|1[012])[.\-\/\s]?((19|20)?\d\d)$/i', $string, $matches1);
        //This checks dates of type Date-Month-Year (where month is represented by string)
        preg_match_all('/^(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?((19|20)?\d\d)$/i', $string, $matches2);

        //This checks dates of type Month-Date-Year (all digits)
        preg_match_all('/^(0?[1-9]|1[012])[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?((19|20)?\d\d)$/i', $string, $matches3);
        //This checks dates of type Month-Date-Year (where month is represented by string)
        preg_match_all('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?((19|20)?\d\d)$/i', $string, $matches4);

        //This checks dates of type Year-Month-Date (all digits)
        preg_match_all('/^((19|20)?\d\d)[.\-\/\s]?(0?[1-9]|1[012])[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])$/i', $string, $matches5);
        //This checks dates of type Year-Month-Date (where month is represented by string)
        preg_match_all('/^((19|20)?\d\d)[.\-\/\s]?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])$/i', $string, $matches6);

        //If any of the above conditions becomes true, then there is a date pattern.
        if (count($matches1[0]) >= 1 || count($matches2[0]) >= 1 || count($matches3[0]) >= 1 
                || count($matches4[0]) >= 1 || count($matches5[0]) >= 1 || count($matches6[0]) >= 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * To check if the string contains a date-like pattern.
     * 
     * @param String $string    The string to be checked
     * @return boolean      Returns true if the string contains a date. False otherwise
     */
    public static function containsDate($string)
    {
        //This checks dates of type Date-Month-Year (all digits)
        preg_match_all('/(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?(0?[1-9]|1[012])[.\-\/\s]?((19|20)?\d\d)/i', $string, $matches1);
        //This checks dates of type Date-Month-Year (where month is represented by string)
        preg_match_all('/(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?((19|20)?\d\d)/i', $string, $matches2);

        //This checks dates of type Month-Date-Year (all digits)
        preg_match_all('/(0?[1-9]|1[012])[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?((19|20)?\d\d)/i', $string, $matches3);
        //This checks dates of type Month-Date-Year (where month is represented by string)
        preg_match_all('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])[.\-\/\s]?((19|20)?\d\d)/i', $string, $matches4);

        //This checks dates of type Year-Month-Date (all digits)
        preg_match_all('/((19|20)?\d\d)[.\-\/\s]?(0?[1-9]|1[012])[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])/i', $string, $matches5);
        //This checks dates of type Year-Month-Date (where month is represented by string)
        preg_match_all('/((19|20)?\d\d)[.\-\/\s]?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.\-\/\s]?(0?[1-9]|[12][0-9]|3[01])/i', $string, $matches6);

        //If any of the above conditions becomes true, then there is a date pattern.
        if (count($matches1[0]) >= 1 || count($matches2[0]) >= 1 || count($matches3[0]) >= 1 
                || count($matches4[0]) >= 1 || count($matches5[0]) >= 1 || count($matches6[0]) >= 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * To check if the string contains double words such as crabcrab, stopstop, treetree, passpass, etc.
     * 
     * @param string $string    The string to be checked
     * @return boolean      Returns true if the string contains double words. False otherwise
     */
    public static function containDoubledWords($string)
    {
        return (preg_match('/(.{3,})\\1/', $string) == 1);
    }

    /**
     * To check if the given string(Hay) contains another string (Needle) in it.
     * Used for checking if the password contains usernames, firstname, lastname etc. 
     * Usually a password must not contain anything related to the user.
     * 
     * @param string $hay       The bigger string that contains another string
     * @param string $needle    The pattern to search for
     * @return boolean      Returns true if the smaller string is found inside the bigger string. False otherwise
     */
    public static function containsString($hay, $needle)    
    {
        preg_match_all('/(' . $needle . ')/i', $hay, $matches);

        if (count($matches[0]) >= 1){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    /**
     * To calculate the strength of a given string. 
     * The value lies between 0 and 1; 1 being the strongest.
     * 
     * @param string $RawPassword   The string whose strength is to be calculated
     * @return float        Strength of the string
     */
    public static function strength($RawPassword)
    {
        $score = 0;

        //initial score is the entropy of the password
        $entropy = self::entropy($RawPassword);
        $score += $entropy / 4;   //maximum entropy is 8
        
        //check for common patters
        $ordered = self::hasOrderedCharacters($RawPassword, strlen($RawPassword) / 2);
        $fullyOrdered = self::hasOrderedCharacters($RawPassword, strlen($RawPassword));
        $hasKeyboardOrder = self::hasKeyboardOrderedCharacters($RawPassword, strlen($RawPassword) / 2);
        $keyboardOrdered = self::hasKeyboardOrderedCharacters($RawPassword, strlen($RawPassword));

        if ($fullyOrdered) {
            $score*=.1;
        } elseif ($ordered) {
            $score*=.5;
        }

        if ($keyboardOrdered) {
            $score*=.15;
        } elseif ($hasKeyboardOrder) {
            $score*=.5;
        }

        if (self::isDate($RawPassword)) {
            $score*=.2;
        } elseif (self::containsDate($RawPassword)) {
            $score*=.5;
        }
        
        if (self::isPhoneNumber($RawPassword)) {
            $score*=.5;
        } elseif (self::containsPhoneNumber($RawPassword)) {
            $score*=.9;
        }

        if (self::containDoubledWords($RawPassword)) {
            $score*=.3;
        }

        //check for variety of character types
        preg_match_all('/\d/i', $RawPassword, $matches);   //password contains digits
        $numbers = count($matches[0]) >= 1;

        preg_match_all('/[a-z]/', $RawPassword, $matches); //password contains lowercase alphabets
        $lowers = count($matches[0]) >= 1;

        preg_match_all('/[A-Z]/', $RawPassword, $matches); //password contains uppercase alphabets
        $uppers = count($matches[0]) >= 1;

        preg_match_all('/[^A-z0-9]/', $RawPassword, $matches); //password contains special characters
        $others = count($matches[0]) >= 1;

        //calculate score of the password after checking type of characters present
        $setMultiplier = ($others + $uppers + $lowers + $numbers) / 4;

        //calculate score of the password after checking the type of characters present and the type of patterns present
        $score = $score / 2 + $score / 2 * $setMultiplier;

        return min(1, max(0, $score));
    }

    /**
     * To generate a random string of specified strength.
     * 
     * @param float $Security   The desired strength of the string
     * @return String       string that is of desired strength
     */
    public static function generate($Security = .5)
    {
        $MaxLen = 20;

        if ($Security > .3){
            $UseNumbers = true;
        }else{
            $UseNumbers = false;
        }

        if ($Security > .5){
            $UseUpper = true;
        }else{
            $UseUpper = false;
        }

        if ($Security > .9){
            $UseSymbols = true;
        }else{
            $UseSymbols = false;
        }


        $Length = max($Security * $MaxLen, 4);

        $chars = 'abcdefghijklmnopqrstuvwxyz';

        if ($UseUpper) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($UseNumbers) {
            $chars .= '0123456789';
        }

        if ($UseSymbols) {
            $chars .= '!@#$%^&*()_+-=?.,';
        }

        $Pass = '';

        //$char contains the string that has all the letters we can use in a password.
        //The loop pics a character from $char in random and adds that character to the final $pass variable.
        for ($i = 0; $i < $Length; ++$i){
            $Pass .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $Pass;
    }

}
