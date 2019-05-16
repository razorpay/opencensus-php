<?php

namespace RZP\Gateway\Card\Fss;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base;

class SbiAesCrypto extends Base\AESCrypto
{
    const MODE_CBC  = AES::MODE_CBC;
    const MODE_ECB  = AES::MODE_ECB;

    public function encryptString(string $str)
    {
        $str = $str . str_repeat('^', strlen($str) % 8);

        $newStr = '';
        for ($index = 0; $index < strlen($str); $index++)
        {
            $hexVal = bin2hex($str[$index]);

            $newStr .= str_pad($hexVal, 2, '^', 0);
        }

        $cipherdText = parent::encryptString(strtoupper($newStr));

        return bin2hex($cipherdText);
    }

    public function decryptString(string $string)
    {
        $decryptedData = parent::decryptString(hex2bin($string));
        $len = strlen($decryptedData);

        $newStr = '';

        for($i = 0; $i<$len; $i = $i + 2)
        {
            $newStr = $newStr . chr(hexdec(substr($decryptedData, $i, 2)));
        }

        $newStr = rtrim($newStr, '^');

        return $newStr;
    }
}
