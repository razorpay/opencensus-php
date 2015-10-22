<?php

namespace Gateway\Sbiepay;

class Security
{
    public static function checkString_e($value)
    {
        $myvalue = ltrim($value);
        $myvalue = rtrim($myvalue);

        if ($myvalue == 'null')
        {
            $myvalue = '';
        }

        return $myvalue;
    }

    public static function getArray2Str($arrayList)
    {
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value)
        {
            if ($flag)
            {
                $paramStr .= self::checkString_e($value);
                $flag = 0;
            }
            else
            {
                $paramStr .= "|" . self::checkString_e($value);
            }
        }

        return $paramStr;
    }


    public static function encrypt($content, $key)
    {
        $aes = new CryptAES();
        $aes->set_key(base64_decode($key));
        $aes->require_pkcs5();

        foreach ($content as &$value)
        {
            $value = $aes->encrypt(self::getArray2Str($value));
        }

        return $content;
    }

    public static function decrypt($str, $key)
    {
        $aes = new CryptAES();
        $aes->set_key(base64_decode($key));
        $aes->require_pkcs5();
        $data = $aes->decrypt($str);

        return $data;
    }

}