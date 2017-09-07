<?php

namespace RZP\Gateway\Netbanking\Indusind;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function encryptString(string $string)
    {
        return bin2hex(parent::encryptString($string));
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt(hex2bin($string));
    }
}
