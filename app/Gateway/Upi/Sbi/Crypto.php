<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Gateway\Base\AESCrypto;

class Crypto extends AESCrypto
{
    public function __construct($mode, $masterKey, $initializationVector = '')
    {
        $masterKey = hex2bin($masterKey);

        parent::__construct($mode, $masterKey, $initializationVector);
    }

    // TODO: Verify this
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
