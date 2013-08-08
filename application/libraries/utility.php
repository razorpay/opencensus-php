<?php

class Utility
{
    private function __construct() {}

    public static function generate_token($len)
    {
        return bin2hex(openssl_random_pseudo_bytes($len/2));
    }

    private static function array_prefix($input, $prefix)
    {
        $prefixed_input = array_map(function($val) use($prefix) { return $prefix.$val; }, $input);

        return $prefix_attr_db;
    }

    private static function array_column_prefix($input, $prefix)
    {
        $keys = array();
        $len = strlen($prefix);
        foreach ($input as $key => $value)
        {
            if (stripos($key, $prefix) === 0)
            {
                array_push($keys, $key);
            }
        }

        return array_column($arr, $keys);
    }

    private static function array_join_conjunction($input1, $input2, $conjunction)
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