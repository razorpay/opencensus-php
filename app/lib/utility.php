<?php

class Utility
{
    private function __construct() {}

    public static function generate_token($len)
    {
        return bin2hex(openssl_random_pseudo_bytes($len/2));
    }

    public static function array_prefix(array $input, $prefix)
    {
        $prefixed_input = array_map(function($val) use($prefix) { return $prefix.$val; }, $input);

        return $prefixed_input;
    }

    public static function array_column_prefix(array $input, $prefix)
    {
        $keys = array();
        $len = strlen($prefix);
        foreach ($input[0] as $key => $value)
        {
            if (stripos($key, $prefix) === 0)
            {
                array_push($keys, $key);
            }
        }
        print_r($input[0][0]);
        print_r($input);die();

        return array_column($input, $keys);
    }

    public static function array_join_conjunction(array $input1, array $input2, $conjunction)
    {
        $output = array();

        $count = count($input1);

        if (count($input1) !== count($input2))
        {
            throw new \InvalidArgumentException("Number of elements in both arrays is not equal");
        }

        for ($i = 0; $i < $count; $i++)
        {
            array_push($output, $input1[$i].$conjunction.$input2[$i]);
        }

        return $output;
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

if (! function_exists('dd_bt_wo_args'))
{
    function dd_bt_wo_args($limit = 0)
    {
         sd(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit));
    }
}