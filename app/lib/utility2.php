<?php

use RZP\Exception;

if (! function_exists('validate_keys'))
{
    function validate_keys($data, $rules)
    {
        $invalid_keys = array_diff_keys($data, $rules);

        if (count($invalid_keys) > 0)
        {
            throw new \RZP\Exception\ExtraFieldsException($invalid_keys);
        }
    }
}

if (! function_exists('getTextBetweenStrings'))
{
    function getTextBetweenStrings($string, $start, $end)
    {
        $string = " ".$string;

        $ini = strpos($string, $start);

        if ($ini === false) return null;

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }
}

if (! function_exists('implode_assoc_array'))
{
    function implode_assoc_array(array $array)
    {
        $str = '';
        foreach($array as $key => $value)
        {
            $str .= $key . '=>' . $value . ', ';
        }

        return $str;
    }
}

if (! function_exists('utf8_json_encode'))
{
    function utf8_array_encode(array $data)
    {
        $utf8Data = [];
        foreach ($data as $key => $value)
        {
            /**
             * All this dance is necessary because we only
             * want to run utf8_encode if the input is a string
             * Because utf8_encode return value is always a string
             * and instead of typecasting it back, we only run it
             * if input = string.
             *
             * Another important thing is that we have to run utf8_encode
             * over the keys of the array as well, only if it's a string
             * Otherwise, numeric indexes become string indexes
             */

            /**
             * The mb_detect_encoding check is to make sure that we are not
             * re-encoding something that is already in utf8.
             */

            // Encode the key
            if (is_string($key) and mb_detect_encoding($key) !== 'UTF-8')
            {
                $key = utf8_encode($key);
            }

            // Encode the value
            if (is_array($value))
            {
                $utf8Data[$key] = utf8_array_encode($value);
            }
            else if (is_string($value) and mb_detect_encoding($value) !== 'UTF-8')
            {
                $utf8Data[$key] = utf8_encode($value);
            }
            else
            {
                $utf8Data[$key] = $value;
            }
        }

        return $utf8Data;
    }
    function utf8_json_encode($data, $depth = 512)
    {
        if (is_array($data))
        {
            $utf8Data = utf8_array_encode($data);
        }
        else
        {
            $utf8Data = $data;
        }

        $jsonOptions = JSON_UNESCAPED_UNICODE;

        // We can use JSON_UNESCAPED_UNICODE because our schema allows utf-8
        $json = json_encode($utf8Data, $jsonOptions, $depth);

        return $json;

    }
}
