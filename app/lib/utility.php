<?php

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
        $integer = '' . mt_rand(1, 9);

        for($i = 1; $i < $length; $i++)
        {
            $integer .= mt_rand(0, 9);
        }

        return (int) $integer;
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
