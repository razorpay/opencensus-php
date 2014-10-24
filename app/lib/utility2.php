<?php

use EE\Exception;

if (! function_exists('validate'))
{
    function validate($rules, $data)
    {
        $invalid_keys = array_diff_key($data, $rules);

        if (count($invalid_keys) !== 0)
        {
            throw new Exception\ExtraFieldsException($invalid_keys);
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails())
        {
            $messages = implode('\n', $validation->messages()->all());

            throw new Exception\InvalidArgumentException($messages);
        }
    }
}

if (! function_exists('validate_keys'))
{
    function validate_keys($data, $rules)
    {
        $invalid_keys = array_diff_keys($data, $rules);

        if (count($invalid_keys) > 0)
        {
            throw new EE\Exception\ExtraFieldsException($invalid_keys);
        }
    }
}

if (! function_exists('extractTextBetweenStrings'))
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