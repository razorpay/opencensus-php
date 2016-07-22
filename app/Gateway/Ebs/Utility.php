<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Ebs;

class Utility extends \RZP\Gateway\Utility
{
    public static function returnFields($response)
    {
        $arr = array();

        $values = explode(' ', $response);
        foreach ($values as $val)
        {
            if (strpos($val, '=') !== false)
            {
                $keyVal = explode('=', $val);
                $arr[$keyVal[0]]= substr($keyVal[1], 1, -1);
            }
        }

        return $arr;
    }

    public static function parseResponseXml($response)
    {

        $fields = self::returnFields($response);

        if (array_key_exists('error', $fields))
        {
            $err = array(
                'errorCode' => $fields['errorCode'],
                'error' => $fields['error'],
            );

            return $err;
        }
        else
        {
            $fields['error'] = false;
            $fields['errorCode'] = 0;

            return $fields;
        }
    }
}
