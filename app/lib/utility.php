<?php

/**
 * getallheaders() polyfill for nginx servers
 *
 * From http://php.net/manual/en/function.getallheaders.php
 */
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];

        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headerKey = str_replace(
                        ' ',
                        '-',
                                ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));

                $headers[$headerKey] = $value;
            }
        }

        return $headers;
    }
}

if (! function_exists('array_merge_intersect'))
{
    function array_merge_intersect(array &$array1, $array2, $array3)
    {
        $intersect = array_intersect($array2, $array3);

        $array1 = array_merge($array1, $intersect);

        return $array1;
    }
}

if (! function_exists('array_assoc_flatten'))
{
    function array_assoc_flatten(array $array, $parent_key = null)
    {
        $return = array();

        foreach ($array as $key => $value)
        {
            $key = ($parent_key === null) ? $key : $parent_key . '.' . $key;
            if (is_array($value))
            {
                $tmp = array_assoc_flatten($value, $key);
                $return = array_merge($return, $tmp);
            }
            else
            {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}

if (! function_exists('get_last_query'))
{
    function get_last_query()
    {
        $queries = DB::getQueryLog();

        $last_query = end($queries);

        return $last_query;
    }
}

if (! function_exists('print_last_query'))
{
    function print_last_query()
    {
        var_dump(get_last_query());
    }
}

if (! function_exists('enable_query_logs'))
{
    function enable_query_logs()
    {
        DB::connection('live')->enableQueryLog();
        DB::connection('test')->enableQueryLog();
    }
}

if (! function_exists('sddb'))
{
    function sddb($limit = 0)
    {
        sd(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit));
    }
}

if (! function_exists('sdb'))
{
    function sdb($limit = 0)
    {
        s(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit));
    }
}

if (! function_exists('ddd'))
{
    function ddd($limit = 0)
    {
        dd(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit));
    }
}

if (! function_exists('random_integer'))
{
    function random_integer($length = 1)
    {
        $min = 10**($length - 1);
        $max = 10**($length) - 1;
        return random_int($min, $max);
    }
}

if (! function_exists('random_alpha_string'))
{
    function random_alpha_string($length = 1)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($chars), 0, $length);
    }
}

if (! function_exists('random_alphanum_string'))
{
    function random_alphanum_string($length = 1)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz1234567890';

        return substr(str_shuffle($chars), 0, $length);
    }
}

if (! function_exists('get_var_in_string'))
{
    function get_var_in_string($var)
    {
        ob_start();
        print_r($var);
        return ob_get_clean();
    }
}

if (! function_exists('array_replace_intersect'))
{
    function array_replace_intersect($array1, $array2)
    {
        foreach ($array1 as $key => $value)
        {
            if (isset($array2[$key]) === true)
                $array1[$key] = $array2[$key];
        }

        return $array1;
    }
}

if (! function_exists('flatten_array'))
{
    function flatten_array($array, $separator = '.', $prefix = '')
    {
        $result = array();

        foreach ($array as $key => $value)
        {
            $newKey = $prefix . (empty($prefix) ? '' : $separator) . $key;

            if (is_array($value))
            {
                $result = array_merge($result, flatten_array($value, $separator, $newKey));
            }
            else
            {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}

/**
 * We do not check for whether this function is defined already
 * If it is defined already by some other library (like phpunit)
 * then we want this definition to be the correct one.
 */
function assertTrue($assertion, $message = null)
{
    if (version_compare(phpversion(), '7.0.0', '<'))
    {
        $message = $message ?: '';

        assert($assertion, $message);
    }
    else
    {
        $e = new RZP\Exception\AssertionException($message);

        assert($assertion, $e);
    }
}

function gen_uuid($format = '%04x%04x%04x%04x%04x%04x%04x%04x')
{
    $uuid = sprintf($format,
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );

    return $uuid;
}

function is_associative_array(array $input)
{
    return array_keys($input) !== range(0, count($input) - 1);
}

function is_sequential_array(array $input)
{
    return array_keys($input) === range(0, count($input) - 1);
}

function upi_uuid($prefix = true)
{
    $uuid = strtoupper(gen_uuid());

    if ($prefix)
    {
        $uuid = 'RAZ' . $uuid;
    }

    return $uuid;
}

function upi_ts() {
    return date('c');
}

/**
 * This function is adapted from Twig/Core
 * See original source at https://git.io/vDumO
 * Twig is licenced under the 3-Clause BSD License
 * @see goo.gl/8ghQeE (OWASP Escaping Guidelines) for the need
 * @param  string $str input string
 * @return string
 */
function escape_html_attribute(string $str)
{
    return preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', function ($matches)
    {
        /**
         * This function is adapted from code coming from Zend Framework.
         *
         * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
         * @license   http://framework.zend.com/license/new-bsd New BSD License
         */
        /*
         * While HTML supports far more named entities, the lowest common denominator
         * has become HTML5's XML Serialisation which is restricted to the those named
         * entities that XML supports. Using HTML entities would result in this error:
         *     XML Parsing Error: undefined entity
         */
        static $entityMap = [
            34 => 'quot', /* quotation mark */
            38 => 'amp',  /* ampersand */
            60 => 'lt',   /* less-than sign */
            62 => 'gt',   /* greater-than sign */
        ];

        $chr = $matches[0];
        $ord = ord($chr);
        /*
         * The following replaces characters undefined in HTML with the
         * hex entity for the Unicode replacement character.
         */
        if (($ord <= 0x1f and $chr != "\t" and $chr != "\n" and $chr != "\r") or ($ord >= 0x7f and $ord <= 0x9f))
        {
            return '&#xFFFD;';
        }
        /*
         * Check if the current character to escape has a name entity we should
         * replace it with while grabbing the hex value of the character.
         */
        if (strlen($chr) == 1)
        {
            $hex = strtoupper(substr('00'.bin2hex($chr), -2));
        }
        else
        {
            $chr = iconv($chr, 'UTF-16BE', 'UTF-8');
            $hex = strtoupper(substr('0000'.bin2hex($chr), -4));
        }
        $int = hexdec($hex);

        if (array_key_exists($int, $entityMap))
        {
            return sprintf('&%s;', $entityMap[$int]);
        }
        /*
         * Per OWASP recommendations, we'll use hex entities for any other
         * characters where a named entity does not exist.
         */
        return sprintf('&#x%s;', $hex);
    }, $str);
}

if (! function_exists('isJson'))
{
    function isJson($string)
    {
        if (is_string($string) === false)
        {
            return false;
        }

        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}

/**
 * For each character in the string checks if the ascii value is
 * greater than 240 or not. Any character with a value greater than 240
 * indicates that it is a 4 byte sequence and hence cannot be considered
 * valid UTF-8 as we don't support utf8mb4 encoding.
 * Ref- https://stackoverflow.com/questions/16496554/can-php-detect-4-byte-encoded-utf8-chars/16496730#16496730
 *
 * @param  string $string value to check
 * @return boolean
 */
function is_valid_utf8(String $string)
{
    return (max(array_map('ord', str_split($string))) < 240);
}

if (! function_exists('camel_case_array'))
{
    /**
     * Camel cases all values of given array and
     * returns the new array.
     *
     * @param array $arr
     *
     * @return array
     */
    function camel_case_array(array $arr)
    {
        return array_map(
                    function ($v)
                    {
                        return camel_case($v);
                    },
                    $arr);
    }
}

if (! function_exists('encode_currency'))
{
    function encode_currency(string $str)
    {
        return str_replace('₹', '&#8377;', $str);
    }
}

/**
* @param $needle
* @param array $haystack An associative array with array values.
*                        ['a' => ['b', 'c'], 'd' => ['e', 'f']]
* @return int|string|null
*/
function get_key_from_subarray_match($needle, array $haystack)
{
    foreach ($haystack as $key => $subArray)
    {
        if (in_array($needle, $subArray, true) === true)
        {
            return $key;
        }
    }

    return null;
}
