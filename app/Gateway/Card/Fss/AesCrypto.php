<?php

namespace RZP\Gateway\Card\Fss;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base;

class AesCrypto extends Base\AESCrypto
{
    const MODE_CBC  = AES::MODE_CBC;

    public function encryptString(string $str)
    {
        $cipherdText = parent::encryptString($str);

        return utf8_encode(base64_encode($cipherdText));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(base64_decode($string));
    }
}
