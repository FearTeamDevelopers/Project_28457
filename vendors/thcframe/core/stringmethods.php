<?php

namespace THCFrame\Core;

/**
 * Description of StringMethods
 *
 * @author Tomy
 */
class StringMethods
{

    private static $_delimiter = '#';
    private static $_singular = array(
        '(matr)ices$' => '\\1ix',
        '(vert|ind)ices$' => '\\1ex',
        '^(ox)en' => '\\1',
        '(alias)es$' => '\\1',
        '([octop|vir])i$' => '\\1us',
        '(cris|ax|test)es$' => '\\1is',
        '(shoe)s$' => '\\1',
        '(o)es$' => '\\1',
        '(bus|campus)es$' => '\\1',
        '([m|l])ice$' => '\\1ouse',
        '(x|ch|ss|sh)es$' => '\\1',
        '(m)ovies$' => '\\1\\2ovie',
        '(s)eries$' => '\\1\\2eries',
        '([^aeiouy]|qu)ies$' => '\\1y',
        '([lr])ves$' => '\\1f',
        '(tive)s$' => '\\1',
        '(hive)s$' => '\\1',
        '([^f])ves$' => '\\1fe',
        '(^analy)ses$' => '\\1sis',
        '((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$' => '\\1\\2sis',
        '([ti])a$' => '\\1um',
        '(p)eople$' => '\\1\\2erson',
        '(m)en$' => '\\1an',
        '(s)tatuses$' => '\\1\\2tatus',
        '(c)hildren$' => '\\1\\2hild',
        '(n)ews$' => '\\1\\2ews',
        '([^u])s$' => '\\1'
    );
    private static $_plural = array(
        '^(ox)$' => '\\1\\2en',
        '([m|l])ouse$' => '\\1ice',
        '(matr|vert|ind)ix|ex$' => '\\1ices',
        '(x|ch|ss|sh)$' => '\\1es',
        '([^aeiouy]|qu)y$' => '\\1ies',
        '(hive)$' => '\\1s',
        '(?:([^f])fe|([lr])f)$' => '\\1\\2ves',
        'sis$' => 'ses',
        '([ti])um$' => '\\1a',
        '(p)erson$' => '\\1eople',
        '(m)an$' => '\\1en',
        '(c)hild$' => '\\1hildren',
        '(buffal|tomat)o$' => '\\1\\2oes',
        '(bu|campu)s$' => '\\1\\2ses',
        '(alias|status|virus)' => '\\1es',
        '(octop)us$' => '\\1i',
        '(ax|cris|test)is$' => '\\1es',
        's$' => 's',
        '$' => 's'
    );
    private static $_diacriticalConversionTable = Array(
        'ä' => 'a',
        'Ä' => 'A',
        'á' => 'a',
        'Á' => 'A',
        'à' => 'a',
        'À' => 'A',
        'ã' => 'a',
        'Ã' => 'A',
        'â' => 'a',
        'Â' => 'A',
        'č' => 'c',
        'Č' => 'C',
        'ć' => 'c',
        'Ć' => 'C',
        'ď' => 'd',
        'Ď' => 'D',
        'ě' => 'e',
        'Ě' => 'E',
        'é' => 'e',
        'É' => 'E',
        'ë' => 'e',
        'Ë' => 'E',
        'è' => 'e',
        'È' => 'E',
        'ê' => 'e',
        'Ê' => 'E',
        'í' => 'i',
        'Í' => 'I',
        'ï' => 'i',
        'Ï' => 'I',
        'ì' => 'i',
        'Ì' => 'I',
        'î' => 'i',
        'Î' => 'I',
        'ľ' => 'l',
        'Ľ' => 'L',
        'ĺ' => 'l',
        'Ĺ' => 'L',
        'ń' => 'n',
        'Ń' => 'N',
        'ň' => 'n',
        'Ň' => 'N',
        'ñ' => 'n',
        'Ñ' => 'N',
        'ó' => 'o',
        'Ó' => 'O',
        'ö' => 'o',
        'Ö' => 'O',
        'ô' => 'o',
        'Ô' => 'O',
        'ò' => 'o',
        'Ò' => 'O',
        'õ' => 'o',
        'Õ' => 'O',
        'ő' => 'o',
        'Ő' => 'O',
        'ř' => 'r',
        'Ř' => 'R',
        'ŕ' => 'r',
        'Ŕ' => 'R',
        'š' => 's',
        'Š' => 'S',
        'ś' => 's',
        'Ś' => 'S',
        'ť' => 't',
        'Ť' => 'T',
        'ú' => 'u',
        'Ú' => 'U',
        'ů' => 'u',
        'Ů' => 'U',
        'ü' => 'u',
        'Ü' => 'U',
        'ù' => 'u',
        'Ù' => 'U',
        'ũ' => 'u',
        'Ũ' => 'U',
        'û' => 'u',
        'Û' => 'U',
        'ý' => 'y',
        'Ý' => 'Y',
        'ž' => 'z',
        'Ž' => 'Z',
        'ź' => 'z',
        'Ź' => 'Z'
    );

    /**
     * 
     */
    private function __construct()
    {
        
    }

    /**
     * 
     */
    private function __clone()
    {
        
    }

    /**
     * Normalization of regular expression strings, so that the remaining 
     * methods can operate on them without first having to check or normalize them
     * 
     * @param type $pattern
     * @return type
     */
    private static function _normalize($pattern)
    {
        return self::$_delimiter . trim($pattern, self::$_delimiter) . self::$_delimiter;
    }

    /**
     * 
     * @return type
     */
    public static function getDelimiter()
    {
        return self::$_delimiter;
    }

    /**
     * 
     * @param type $delimiter
     */
    public static function setDelimiter($delimiter)
    {
        self::$_delimiter = $delimiter;
    }

    /**
     * Methods perform similarly to the preg_match_all() function, but require less
     * formal structure to the regular expressions, and return a more predictable set of results. 
     * The match() method will return the first captured substring, 
     * the entire substring match, or null.
     * 
     * @param type $string
     * @param type $pattern
     * @return null
     */
    public static function match($string, $pattern)
    {
        preg_match_all(self::_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

        if (!empty($matches[1])) {
            return $matches[1];
        }

        if (!empty($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Methods perform similarly to the preg_split() functions, but require less
     * formal structure to the regular expressions, and return a more predictable set of results. 
     * The split() method will return the results
     * of a call to the preg_split() function, after setting some flags and normalizing the regular expression.
     * 
     * @param type $string
     * @param type $pattern
     * @param type $limit
     * @return type
     */
    public static function split($string, $pattern, $limit = null)
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        return preg_split(self::_normalize($pattern), $string, $limit, $flags);
    }

    /**
     * Method loops through the characters of a string, replacing them with 
     * regular expression friendly character representations
     * 
     * @param type $string
     * @param type $mask
     * @return type
     */
    public static function sanitize($string, $mask)
    {
        if (is_array($mask)) {
            $parts = $mask;
        } else if (is_string($mask)) {
            $parts = str_split($mask);
        } else {
            return $string;
        }

        foreach ($parts as $part) {
            $normalized = self::_normalize("\\{$part}");
            $string = preg_replace("{$normalized}m", "\\{$part}", $string);
        }

        return $string;
    }

    /**
     * Method eliminates all duplicated characters in a string
     * 
     * @param type $string
     * @return type
     */
    public static function unique($string)
    {
        $unique = '';
        $parts = str_split($string);

        foreach ($parts as $part) {
            if (!strstr($unique, $part)) {
                $unique .= $part;
            }
        }

        return $unique;
    }

    /**
     * Method returns the position of a substring within a larger string, 
     * or -1 if the substring isn’t found
     * 
     * @param type $string
     * @param type $substring
     * @param type $offset
     * @return type
     */
    public static function indexOf($string, $substring, $offset = null)
    {
        $position = strpos($string, $substring, $offset);
        if (!is_int($position)) {
            return -1;
        }
        return $position;
    }

    /**
     * 
     * @param type $string
     * @param type $substring
     * @param type $offset
     * @return type
     */
    public static function lastIndexOf($string, $substring, $offset = null)
    {
        $position = strrpos($string, $substring, $offset);
        if (!is_int($position)) {
            return -1;
        }
        return $position;
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    public static function singular($string)
    {
        $result = $string;

        foreach (self::$_singular as $rule => $replacement) {
            $rule = self::_normalize($rule);

            if (preg_match($rule, $string)) {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }

        return $result;
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    public static function plural($string)
    {
        $result = $string;

        foreach (self::$_plural as $rule => $replacement) {
            $rule = self::_normalize($rule);

            if (preg_match($rule, $string)) {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }

        return $result;
    }

    /**
     * Method remove diacritical marks form string
     * 
     * @param type $string
     * @return type
     */
    public static function removeDiacriticalMarks($string)
    {
        return strtr($string, self::$_diacriticalConversionTable);
    }

    /**
     * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
     *
     * @param string $text String to truncate.
     * @param integer $length Length of returned string, including ellipsis.
     * @param string $ending Ending to be appended to the trimmed string.
     * @param boolean $exact If false, $text will not be cut mid-word
     * @param boolean $considerHtml If true, HTML tags would be handled correctly
     *
     * @return string Trimmed string.
     */
    public static function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

}
