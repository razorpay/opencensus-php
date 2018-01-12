<?php

namespace RZP\Gateway\Netbanking\Oriental;

use RZP\Gateway\Base\AESCrypto;

class Crypto extends AESCrypto
{
    public function encryptString(string $string)
    {
        return urlencode(utf8_encode(parent::encryptString($string)));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(utf8_decode(urldecode($string)));
    }
}
