<?php

namespace THCFrame\Core;

/**
 * Class handles generation of random strings and numbers
 */
class Rand
{

    /**
     * The seed from which a random number will be generated.
     * @var int
     */
    protected static $randomSeed = null;

    /**
     * Provides a random 32 bit number, if openssl is available, it is 
     * cryptographically secure. Otherwise all available entropy is gathered.
     * 
     * @return number
     */
    public static function random()
    {
        //If openssl is present, use that to generate random.
        if (function_exists("openssl_random_pseudo_bytes") && FALSE) {
            $random32bit = (int) (hexdec(bin2hex(openssl_random_pseudo_bytes(64))));
        } else {
            if (self::$randomSeed === null) {
                $entropy = 1;

                if (function_exists("posix_getpid")) {
                    $entropy*=posix_getpid();
                }

                if (function_exists("memory_get_usage")) {
                    $entropy*=memory_get_usage();
                }

                list ($usec, $sec) = explode(" ", microtime());
                $usec*=1000000;
                $entropy*=$usec;
                self::$randomSeed = $entropy;

                mt_srand(self::$randomSeed);
            }

            $random32bit = mt_rand();
        }

        return $random32bit;
    }

    /**
     * To generate a random number between the specified range
     * 
     * @param int $min
     * @param int $max
     * @return number
     */
    public static function randRange($min = 0, $max = null)
    {
        if ($max === null) {
            $max = 1 << 31;
        }

        if ($min > $max) {
            return self::randRange($max, $min);
        }

        if ($min >= 0) {
            return abs(self::random()) % ($max - $min) + $min;
        } else {
            return (abs(self::random()) * -1) % ($min - $max) + $max;
        }
    }

    /**
     * To generate a random string of specified length
     * 
     * @param int $length
     * @return String
     */
    public static function randStr($length = 32)
    {
        /* Use `openssl_random_psuedo_bytes` if available; PHP5 >= 5.3.0 */
        if (function_exists("openssl_random_pseudo_bytes")) {
            return substr(bin2hex(openssl_random_pseudo_bytes($length)), 0, $length);
        }

        /* Fall back to `mcrypt_create_iv`; PHP4, PHP5 */
        if (function_exists('mcrypt_create_iv')) {
            return substr(bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)), 0, $length);
        }

        $sha = '';
        $rnd = '';
        
        for ($i = 0; $i < $length; $i++) {
            $sha = hash('sha256', $sha . mt_rand());
            $char = mt_rand(0, 62);
            $rnd .= $sha[$char];
        }

        return $rnd;
    }

}
