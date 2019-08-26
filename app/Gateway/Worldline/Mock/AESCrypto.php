<?php

namespace RZP\Gateway\Worldline\Mock;

use RZP\Gateway\Worldline;

class AESCrypto extends Worldline\AESCrypto
{
    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }
}
