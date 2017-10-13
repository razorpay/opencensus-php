<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Gateway\Base\AESCrypto;

class Crypto extends AESCrypto
{
    // TODO: Verify this
    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(base64_decode($string));
    }
}