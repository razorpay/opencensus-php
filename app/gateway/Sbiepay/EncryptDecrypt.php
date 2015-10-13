<?php

namespace Gateway\Sbiepay;

class EncryptDecrypt
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


    public static function encryptData($dataArray)
    {
        $aes = new CryptAES();
        $aes->set_key(base64_decode($_ENV['SBIEPAY_GATEWAY_TEST_HASH_SECRET']));
        $aes->require_pkcs5();

        foreach ($dataArray as &$data)
        {
            $data = $aes->encrypt(self::getArray2Str($data));
        }

        return $dataArray;
    }


    public static function decryptData($dataStr)
    {
        $aes = new CryptAES();
        $aes->set_key(base64_decode($_ENV['SBIEPAY_GATEWAY_TEST_HASH_SECRET']));
        $aes->require_pkcs5();
        $data = $aes->decrypt($dataStr);

        return $data;
    }

}