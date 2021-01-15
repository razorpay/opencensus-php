<?php

namespace RZP\Gateway\Paytm;
use RZP\Constants\HashAlgo;

// Adapted for php7.1 using code at
// https://github.com/Paytm-Payments/Paytm_App_Checksum_Kit_PHP/issues/1
class Checksum
{
    const CHECKSUMHASH = 'CHECKSUMHASH';
    const IV = '@@@@&&&&####$$$$';
    const AES_128_CBC = 'AES-128-CBC';
    const TRUE = 'TRUE';
    const FALSE = 'FALSE';
    const REFUND = 'REFUND';
    const STR_NULL = 'null';

    public static function encrypt_e($input, $ky)
    {
        $key   = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
        return $data;
    }


    public static function decrypt_e($crypt, $ky) {
        $key   = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
        return $data;
    }

    public static function generateSalt_e($length)
    {
        $random = "";
        srand((double) microtime() * 1000000);
        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";
        for ($i = 0; $i < $length; $i++)
        {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        return $random;
    }

    public static function generateSaltV2_e($length)
    {
        $random = "";
        srand((double) microtime() * 1000000);
        $data = "9876543210ZYXWVUTSRQPONMLKJIHGFEDCBAabcdefghijklmnopqrstuvwxyz!@#$&_";
        for ($i = 0; $i < $length; $i++)
        {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        return $random;
    }

    public static function checkString_e($value)
    {
        if ($value == 'null')
        {
            $value = '';
        }
        return $value;
    }

    public  static function getChecksumFromArray($arrayList, $key, $sort = 1)
    {
        if ($sort != 0)
        {
            ksort($arrayList);
        }
        $str = self::getArray2Str($arrayList);
        $salt = self::generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = self::encrypt_e($hashString, $key);
        return $checksum;
    }

    public static function getChecksumFromString($str, $key)
    {
        $salt = self::generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = self::encrypt_e($hashString, $key);
        return $checksum;
    }

    public static function verifychecksum_e($arrayList, $key, $checksumvalue)
    {
        ksort($arrayList);
        $str = self::getArray2StrForVerify($arrayList);
        $paytm_hash = self::decrypt_e($checksumvalue, $key);

        // decryption fails
        if ($paytm_hash === false)
        {
            return false;
        }

        $salt = substr($paytm_hash, -4);
        $finalString = $str . "|" . $salt;
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        if (hash_equals($website_hash, $paytm_hash))
        {
            $validFlag = true;
        }
        else
        {
            $validFlag = false;
        }
        return $validFlag;
    }

    public static function verifychecksum_eFromStr($str, $key, $checksumvalue)
    {
        $paytm_hash = self::decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);
        $finalString = $str . "|" . $salt;
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        return hash_equals($paytm_hash, $website_hash);
    }

    public static function getArray2Str($arrayList)
    {
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value)
        {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos !== false || $pospipe !== false)
            {
                continue;
            }
            if ($flag)
            {
                $paramStr .= self::checkString_e($value);
                $flag = 0;
            }
            else
            {
                $paramStr .= "|" .self::checkString_e($value);
            }
        }
        return $paramStr;
    }

    public static function getArray2StrForVerify($arrayList)
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

    public static function getRefundChecksumFromArray($arrayList, $key, $sort=1)
    {
        if ($sort != 0)
        {
            ksort($arrayList);
        }
        $str = self::getRefundArray2Str($arrayList);
        $salt = self::generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = self::encrypt_e($hashString, $key);

        return $checksum;
    }

    public static function getRefundArray2Str($arrayList)
    {
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value)
        {
            $pospipe = strpos($value, $findmepipe);
            if ($pospipe !== false)
            {
                continue;
            }

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

}
