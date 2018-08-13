<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        parent::__construct($mode, $masterKey);
    }

    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }

    public function decryptString(string $string)
    {
        $this->aes->disablePadding();

        return parent::decryptString(base64_decode($string));
    }
}
