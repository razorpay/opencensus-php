<?php

namespace RZP\Gateway\Netbanking\Obc;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        $masterKey = str_pad($masterKey, 16, $masterKey);

        parent::__construct($mode, $masterKey, $initializationVector);
    }

    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(base64_decode($string));
    }
}
