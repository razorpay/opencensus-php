<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Gateway\Upi\Mindgate\Crypto as BaseCrypto;

class Crypto extends BaseCrypto
{
    public function encryptString(string $string)
    {
        $cipherText = parent::encryptString($string);

        return strtoupper(bin2hex($cipherText));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(hex2bin($string));
    }
}
