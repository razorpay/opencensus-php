<?php

namespace Gateway\Sbiepay;

class EncryptDecrypt
{
    public static function encrypt_e($input, $ky)
    {
        $input = self::getArray2Str($input);
        $key = base64_decode($ky);
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
        $input = self::pkcs5_pad_e($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public static function decrypt_e($crypt, $ky)
    {

        $crypt = base64_decode($crypt);
        $key = base64_decode($ky);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $crypt);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted_data = self::pkcs5_unpad_e($decrypted_data);
        $decrypted_data = rtrim($decrypted_data);
        return $decrypted_data;
    }

    public static function pkcs5_pad_e($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function pkcs5_unpad_e($text)
    {
        $pad = ord($text{strlen($text) - 1});

        if ($pad > strlen($text))
        {
            return false;
        }

        return substr($text, 0, -1 * $pad);
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
            } else
            {
                $paramStr .= "|" . self::checkString_e($value);
            }
        }
        return $paramStr;
    }

}