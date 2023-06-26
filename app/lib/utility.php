<?php

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Date;
use RZP\Constants\Util;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Exception\AssertionException;
use RZP\Exception\BadRequestException;
use Google\Protobuf\Struct as ProtobufStuct;

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
    function array_assoc_flatten(array $array, string $separatorFormat = "%s.%s", $parent_key = null)
    {
        $return = array();

        foreach ($array as $key => $value)
        {
            $key = ($parent_key === null) ? $key : sprintf($separatorFormat, $parent_key, $key);
            if (is_array($value))
            {
                $tmp = array_assoc_flatten($value, $separatorFormat, $key);
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

if (! function_exists('array_assoc_flatten_nth_level'))
{
    /**
     * Same as array_assoc_flatten but restrict to n-th level of nesting
     */
    function array_assoc_flatten_nth_level(array $array, string $separatorFormat = "%s.%s", $parent_key = null, $flattenSequential = false, $uptoLevel = 7, $level = 0)
    {
        $return = array();

        foreach ($array as $key => $value)
        {
            $shouldFlatten = $level < $uptoLevel;

            $key = ($parent_key === null) ? $key : sprintf($separatorFormat, $parent_key, $key);
    
            if ($shouldFlatten && is_array($value))
            {
                $shouldFlattenArray = (is_sequential_array($value) === false || $flattenSequential === true);

                if ($shouldFlattenArray)
                {
                    $tmp = array_assoc_flatten_nth_level($value, $separatorFormat, $key, $flattenSequential, $uptoLevel, $level + 1);
                    $return = array_merge($return, $tmp);
                }
                else
                {
                    $return[$key] = $value;
                }
            }
            else
            {
                $return[$key] = $value;
            }

        }


        return $return;
    }
}

if (! function_exists('array_unset_recursive'))
{
    function array_unset_recursive(array &$array, $remove)
    {
        if (!is_array($remove))
        {
            $remove = array($remove);
        }

        foreach ($array as $key => &$value) {
            if (in_array($value, $remove))
            {
                unset($array[$key]);
            }

            else if (is_array($value))
            {
                array_unset_recursive($value, $remove);
            }
        }
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

if (! function_exists('array_delete'))
{
    function array_delete($del_val, & $arr)
    {
        if (($key = array_search($del_val, $arr)) !== false)
        {
            unset($arr[$key]);
        }
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

if (! function_exists('random_string_special_chars'))
{
    function random_string_special_chars($length = 1)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz:';

        return substr(str_shuffle($chars), 0, $length);
    }
}

/**
 * Does a case insensitive search in an array (sequential/associative)
 *
 * @param       $needle
 * @param array $haystack
 *
 * @return false|int|string
 */
function array_search_ci($needle, array $haystack)
{
    return array_search(strtolower($needle), array_map('strtolower', $haystack));
}

/**
 * We do not check for whether this function is defined already
 * If it is defined already by some other library (like phpunit)
 * then we want this definition to be the correct one.
 */
function assertTrue($assertion, $message = null)
{
    if (boolval($assertion) !== true)
    {
        throw new AssertionException($message);
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

/**
 * This function does array_combine after making both array to the
 * same size, by slicing the bigger length array to the smaller one
 *
 * @param array $headers
 * @param array $columns
 * @return array
 */
function array_combine_slice(array $headers, array $columns)
{
    $headersCount = count($headers);
    $columnsCount = count($columns);

    $size = ($headersCount > $columnsCount) ? $columnsCount : $headersCount;

    $headers = array_slice($headers, 0, $size);
    $columns = array_slice($columns, 0, $size);

    return array_combine($headers, $columns);
}

function array_combine_pad(array $headers, array $columns)
{
    $headersCount = count($headers);
    $columnsCount = count($columns);

    if ($headersCount > $columnsCount)
    {
        $extra = $headersCount - $columnsCount;

        for ($i = 0; $i < $extra; $i++)
        {
            $columns[] = null;
        }
    }
    // more fields than headers
    else if ($headersCount < $columnsCount)
    {
        $extra = $columnsCount - $headersCount;

        // Needs to start from 1 so that the first
        // extra field is named as extra_field_1
        for($i = 1; $i <= $extra; $i++)
        {
            $key = 'extra_field_' . $i;

            $headers[] = $key;
        }
    }

    return array_combine($headers, $columns);
}

function array_combine_pad_headers(array $headers, array $columns)
{
    $headersCount = count($headers);
    $columnsCount = count($columns);

    $extra = $columnsCount - $headersCount;

    // Needs to start from 1 so that the first
    // extra field is named as extra_field_1
    for($i = 1; $i <= $extra; $i++)
    {
        $key = 'extra_field_' . $i;

        $headers[] = $key;
    }

    return array_combine($headers, $columns);
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

function get_human_readable_size($size)
{
    $unit= ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
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

if (! function_exists('print_jssafe_json')) {
    /**
     * Prints JSON data safe to be included inside a javascript block
     *
     * @param $obj input value to be converted to json
     */
    function print_jssafe_json($obj)
    {
        echo json_encode($obj, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);
    }
}

if (! function_exists('checkRequestTimeout'))
{
    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    function checkRequestTimeout(\WpOrg\Requests\Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occurred
        //
        if ((strpos($msg, 'operation timed out') !== false) or
            (strpos($msg, 'network is unreachable') !== false) or
            (strpos($msg, 'name or service not known') !== false) or
            (strpos($msg, 'failed to connect') !== false) or
            (strpos($msg, 'could not resolve host') !== false) or
            (strpos($msg, 'resolving timed out') !== false) or
            (strpos($msg, 'name lookup timed out') !== false) or
            (strpos($msg, 'connection timed out') !== false) or
            (strpos($msg, 'aborted due to timeout') !== false))
        {
            return true;
        }

        return false;
    }
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

if (! function_exists('seq_array'))
{
    /**
     * Associative to sequential array. For e.g. passing an associative array
     * to redis hmset method can use this method to prepare argument list.
     *
     * @param  array $assocArray
     * @return array
     */
    function seq_array(array $assocArray): array
    {
        if (is_sequential_array($assocArray) === true)
        {
            return $assocArray;
        }

        $seqArray = [];

        foreach ($assocArray as $k => $v)
        {
            $seqArray[] = $k;
            $seqArray[] = $v;
        }

        return $seqArray;
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
* @return mixed
*/
if (! function_exists('get_key_from_subarray_match'))
{
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
}

if (! function_exists('group_array_by_sub_array_value'))
{
    function group_array_by_value_array($groupKey, array $haystack)
    {
        $newArray[$groupKey] = [];

        foreach ($haystack as $key => $subArray)
        {
            if (in_array($groupKey, $subArray, true) === true)
            {
                $newArray[$groupKey][] = $key;
            }
        }

        return $newArray;
    }
}

if (! function_exists('epoch_format'))
{
    /**
     * Formats given epoch to human readable string representation.
     * @param  int    $epoch
     * @param  string $format
     * @return string
     */
    function epoch_format(int $epoch, string $format = Date::DEFAULT_STRING_FORMAT): string
    {
        return date($format, $epoch);
    }
}

if (! function_exists('strtoepoch'))
{
    /**
     * Converts given human readable string representation to epoch
     * @param  string $dateStr
     * @param  string $format
     * @param  bool $startOfDay
     * @return string
     */
    function strtoepoch(string $dateStr, string $format = 'd-M-Y', bool $startOfDay = false): string
    {
        try
        {
            $dt = Carbon::createFromFormat($format, $dateStr, Timezone::IST);

            if ($startOfDay === true)
            {
                $dt = $dt->startOfDay();
            }
        }
        catch (Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'date string'     => $dateStr,
                    'expected format' => $format
                ],
            'Invalid date format');
        }

        return $dt->format('U');
    }
}


if (! function_exists('multidim_array_unique'))
{
    /**
     * Removes the duplicate array values based on a column key
     * @param  array    $input
     * @param  string   $column
     * @return array
     */
    function multidim_array_unique(array $input, string $column): array
    {
        $result = [];

        foreach ($input as $v)
        {
            $colVal = $v[$column];

            if (isset($result[$colVal]) === false)
            {
                $result[$colVal] = $v;
            }
        }

        return array_values($result);
    }

    /**
     * Sorts the given multi dimensional array based on a key
     *
     * @param array  $array
     * @param string $key
     * @param int    $sortOrder
     */
    function sortMultiDimensionalArray(array & $array, string $key, int $sortOrder = SORT_DESC)
    {
        foreach ($array as & $item)
        {
            array_multisort(array_map(function ($element) use ($key)
            {
                return $element[$key];
            }, $item), $sortOrder, $item);

        }
    }
}

if (! function_exists('money_format_IN'))
{
    /**
     * Formats a number as a Indian currency string.
     * Refs:
     * - http://php.net/manual/en/function.money-format.php (Locales files/settings not available on production!)
     * - https://blog.revathskumar.com/2014/11/regex-comma-seperated-indian-currency-format.html
     * @param  string $number - Must be string representation of number, e.g. '-123.45'/'123'/'123.00' etc.
     * @return string
     */
    function money_format_IN(string $number): string
    {
        return preg_replace('/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/', '\\1,', $number);
    }
}

if (! function_exists('amount_format_IN'))
{
    /**
     * Formats paise amount as a Indian currency string.
     *
     * @param  int|null $amount
     * @return string
     */
    function amount_format_IN(int $amount = null): string
    {
        return money_format_IN(number_format($amount / 100, 2, '.', ''));
    }
}

if (! function_exists('millitime'))
{
    /**
     * Gets current unix timestamp in milliseconds
     * @return int
     */
    function millitime(): int
    {
        return round(microtime(true) * 1000);
    }
}

if (! function_exists('stringify'))
{

    /**
     * Stringifies given value, e.g. true -> 'true', 0 -> '0', null -> "null", 10.0 -> "10.0" etc
     * @param  mixed  $value
     * @return string
     */
    function stringify($value): string
    {
        if (is_string($value) === true)
        {
            return $value;
        }

        return json_encode($value);
    }
}

if (! function_exists('str_wrap'))
{
    /**
     * Wraps string with given value: Adds the value as prefix and suffix if not already exists
     * @param  string $value
     * @param  string $wrap
     * @return string
     */
    function str_wrap(string $value, string $wrap): string
    {
        return str_finish(str_start($value, $wrap), $wrap);
    }
}

if (! function_exists('is_rzp_business_hour'))
{
    /**
     * Returns true if now or custom carbon instance passed is a Razorpay business hour.
     * @param  Carbon|null $dateTime
     * @return boolean
     */
    function is_rzp_business_hour(Carbon $dateTime = null): bool
    {
        $dateTime = $dateTime ?: Carbon::now(Timezone::IST);

        // Todo: Use Settlements/Holiday.php once interface and issue is fixed there, that handles holidays.

        return (($dateTime->isWeekday() === true) and
                // Between 9 AM - 6 PM (Both inclusive)
                ($dateTime->hour >= 9) and
                ($dateTime->hour < 18));
    }
}

if (! function_exists('mask_except_last4'))
{
    function mask_except_last4(string $value = null, string $masker = 'X'): ?string
    {
        if (empty($value) === true)
        {
            return $value;
        }

        return str_repeat($masker, max(strlen($value) - 4, 0)) . substr($value, -4);
    }
}

if (! function_exists('get_gamma_channel'))
{
    /**
     * Gets gamma channel of the color
     * ref: https://ux.stackexchange.com/questions/82056/how-to-measure-the-contrast-between-any-given-color-and-white
     *
     * @param String $colorHash
     * @param Integer $startIndex
     * @return Float
     */
    function get_gamma_channel($colorHash, $startIndex): float
    {
        $colorInDec = hexdec(substr($colorHash, $startIndex, 2));

        return $colorInDec <= 10 ? ($colorInDec / 3294) : (pow(($colorInDec / 269) + 0.0513, 2.4));

    }
}


if (! function_exists('get_contrast_with_white'))
{
    /**
     * Compares the contrast of the color with respect to white color
     * ref: https://ux.stackexchange.com/questions/82056/how-to-measure-the-contrast-between-any-given-color-and-white
     * @param String $color
     * @return Float
     */
    function get_contrast_with_white($color): float
    {
        $redGamma       = get_gamma_channel($color, 0);
        $greenGamma     = get_gamma_channel($color, 2);
        $blueGamma      = get_gamma_channel($color, 4);

        return (0.2126 * $redGamma) + (0.7152 * $greenGamma) + (0.0722 * $blueGamma);
    }

}

if (! function_exists('dashboard_url'))
{
    /**
     * Returns dashboard url for given path, for current environment.
     * @param  string $path
     * @return string
     */
    function dashboard_url(string $path = ''): string
    {
        // Domain value from config includes trailing / and hence strips leading / from @path argument if it exists.
        return config('applications.dashboard.url') . str_after($path, '/');
    }
}

if (! function_exists('get_diff_in_millisecond'))
{
    /**
     * Returns time diff in milliseconf for given startTime, and current time.
     * @param  float $startTime, unit is seconds
     * @return int
     */
    function get_diff_in_millisecond(float $startTime): int
    {
        $endTime = microtime(true);

        $requestTime = $endTime - $startTime;

        $requestTime = $requestTime * 1000;

        return (int) $requestTime;
    }
}

if (!function_exists('get_similar_text_percent'))
{
    /**
     * Returns the percentage of similar text between first & second string
     * The percentage is in float. First and second strings are converted to
     * lower case and removes any spaces/tabs if exists.
     *
     * @param string $first
     * @param string $second
     *
     * @return float
     */
    function get_similar_text_percent(string $first, string $second) : float
    {


        $first = strtolower(preg_replace('/\s+/', '', $first));

        $second = strtolower(preg_replace('/\s+/', '', $second));

        $percent = 0;

        similar_text($first, $second, $percent);

        return $percent;
    }
}

if (!function_exists('mask_phone'))
{
    /**
     * Masks the phone-number string except the first 2 and the last 2 digits
     * @param string|null $phone
     * @return string|null
     */
    function mask_phone(string $phone = null)
    {
        if (empty($phone) === true)
        {
            return null;
        }
        $phoneLen = strlen($phone);

        return substr($phone, 0, 2) .
               str_repeat('*', $phoneLen - 4) .
               substr($phone, $phoneLen - 2, 2);
    }
}

if (!function_exists('mask_email'))
{
    /**
     * Masks the customer email as follows
     * Input: test_email@gmail.com
     * Output: tes*****l@g****.com
     *
     * NOTE : for the default mask-percentage, if the
     * length of email is less than 3 characters, then the whole email
     * will get masked
     *
     * @param string $email
     * @param float  $percentageToMask
     *
     * @return mixed|string
     */
    function mask_email(string $email = null, float $percentageToMask = 0.7)
    {
        $trace = App::getFacadeRoot()['trace'];

        $maskedEmail = $email;

        if (empty($email) === true)
        {
            return null;
        }
        try
        {
            // assuming that if this is filled, then its a valid email
            $email = explode('@', $email); // ex: test_email@gmail.com

            $emailName = $email[0]; // test_email

            $emailDomain = $email[1]; // gmail.com

            $emailDomain = explode('.', $emailDomain);

            $domain = $emailDomain[0]; // gmail

            $topLevelDomain = $emailDomain[1]; // .com

            $emailLen = strlen($emailName);

            $lengthToMask = ceil($emailLen * $percentageToMask);

            // replace the name except first 3 characters with *
            $maskedEmailName = substr($emailName, 0, $emailLen - $lengthToMask) .
                               str_repeat('*', max(0,$lengthToMask));

            // replace the domain with *, except the first and the last character
            $maskedDomain     = $domain[0] .
                                str_repeat('*', max(0,strlen($domain) - 2)) .
                                $domain[strlen($domain) - 1];

            $maskedEmail = sprintf('%s@%s.%s', $maskedEmailName, $maskedDomain, $topLevelDomain);
        }
        catch (\Exception $e)
        {
            // Do not want the page load to fail because the email was incorrect
            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::INVALID_EMAIL_CANNOT_MASK,
                                   [
                                       'email' => $email
                                   ]
            );
        }

        return $maskedEmail;
    }
}

if (!function_exists('mask_vpa'))
{
    /**
     * For VPA we do not need to mask the PSP Code
     * We only need to mask the username
     * @param string|null $vpa
     * @return string|null
     */
    function mask_vpa(string $vpa = null)
    {
        if (empty($vpa) === true)
        {
            return null;
        }
        $exploded = explode('@', $vpa);
        // Note last4 do not come in to PII
        // First pad the username to not give the VPA size
        $username = str_pad($exploded[0], 10, '*', STR_PAD_LEFT);

        return mask_except_last4($username, '*') . '@' . ($exploded[1] ?? '');
    }
}

if (!function_exists('wrap_db_table'))
{
    function wrap_db_table(string $table)
    {
        $segments = explode('.', $table);

        return collect($segments)->map(function ($segment, $key) use ($segments) {
            return '`'. $segment .'`';
        })->implode('.');
    }
}

if (!function_exists('array_fetch')) {
    /**
     * Fetch a flattened array of a nested array element.
     *
     * @param array $array
     * @param string $key
     * @param string $delimiter
     * @return array
     */
    function array_fetch($array, $key, $delimiter='.')
    {
        $results = array();

        foreach (explode($delimiter, $key) as $segment) {
            foreach ($array as $value) {
                if (array_key_exists($segment, (array)$value)) {
                    $results[] = $value[$segment];
                }
            }

            $array = $results;
        }

        return array_values($results);
    }
}

if (!function_exists('get_Protobuf_Struct'))
{
    /**
     * Convert Array to Google\Protobuf\Struct type
     */
    function get_Protobuf_Struct(array $arr): ProtobufStuct
    {
        $detailsJsonString = json_encode($arr);

        $struct = new ProtobufStuct();

        $struct->mergeFromJsonString($detailsJsonString);

        return $struct;
    }
}

if (! function_exists('mask_by_percentage'))
{
    function mask_by_percentage(string $data = null, float $percentageToMask = 0.7)
    {
        if (empty($data) === true)
        {
            return $data;
        }

        $dataLen = strlen($data);

        $lengthOfDataToMask = ceil($dataLen * $percentageToMask);

        return substr($data, 0, $dataLen - $lengthOfDataToMask) .
            str_repeat('*', $lengthOfDataToMask);
    }
}

if (! function_exists('isValidHost'))
{
     function isValidHost(string $host): bool
     {
        $domains = Util::RAZORPAY_VALID_DOMAINS;

        for($i = 0; $i < sizeof($domains); $i++)
        {
            if(str_contains($host, $domains[$i]))
            {
                return true;
            }
        }

        return false;
     }
}


if(! function_exists('getOrigin'))
{
    function getOrigin(): string
    {
        $trace = App::getFacadeRoot()['trace'];

        $app = App::getFacadeRoot()['app'];

        $origin_value = "";

        $origin = $app['request']->header(RequestHeader::X_REQUEST_ORIGIN) ?? "";

        try
        {
            $host =  parse_url($origin, PHP_URL_HOST);

            if(empty($host) === false and is_string($host) === true and isValidHost($host) === true)
            {
                $host = preg_replace( '/[^-a-zA-Z.]/i', '', $host);
                // All valid senders has less than 30 characters https://github.com/razorpay/vishnu/blob/b27a63f4dbae1eaf4dec3bf3aca9b1c02454e77e/prod/kubernetes/prod-white/apps/dashboard/dashboards.tf#L46-L61
                $origin_value = '@'.str_limit($host, 29, '');
            }
        }
        catch (Throwable $e)
        {
            $trace->info(TraceCode::DOMAIN_URL_PARSE_FAILURE, [
                'url' => $origin,
            ]);
        }

        return $origin_value;
    }
}

if(! function_exists('check_array_selective_equals_recursive'))
{
    /**
     * Use this to check match between nested data fields for two array recursively
     */
    function check_array_selective_equals_recursive(array $expected, array $actual): bool
    {
        $result = true;

        foreach ($expected as $key => $value)
        {
            $result = isset($actual[$key]);

            if ($result === false) {
                return false;
            }

            if (is_array($value))
            {
                if (is_array($actual[$key]))
                {
                    $result = check_array_selective_equals_recursive($expected[$key], $actual[$key]);
                }
                else
                {
                    // expected value is an array but actual value is not array
                    $result = false;
                }
            }
            else
            {
                $result = ($value === $actual[$key]);
            }

            if ($result === false) {
                return false;
            }
        }

        return $result;
    }
}

if(! function_exists('assign_array_by_flattened_path'))
{
    /**
     * Set the nested key-value in an array based on the flattened key
     */
    function assign_array_by_flattened_path(&$arr, $path, $value, $separator='.')
    {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }
}