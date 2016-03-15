<?php

use EE\Exception;

if (! function_exists('validate'))
{
    function validate($rules, $data, $strict = true)
    {
        $invalid_keys = array_keys(array_diff_key($data, $rules));

        if ((count($invalid_keys) !== 0) and
            ($strict === true))
        {
            throw new Exception\ExtraFieldsException($invalid_keys);
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails())
        {
            $messages = implode('\n', $validation->messages()->all());

            throw new Exception\BadRequestValidationFailureException($messages);
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
            if (is_array($value))
            {
                $utf8Data[utf8_encode($key)] = utf8_array_encode($value);
            }
            else
            {

                $utf8Data[utf8_encode($key)] = utf8_encode($value);
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
            $utf8Data = utf8_encode($data);
        }

        $jsonOptions = JSON_UNESCAPED_UNICODE or JSON_FORCE_OBJECT;

        // We can use JSON_UNESCAPED_UNICODE because our schema allows utf-8
        return json_encode($utf8Data, $jsonOptions, $depth);
    }
}
