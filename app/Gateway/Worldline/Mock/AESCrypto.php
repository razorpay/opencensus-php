<?php

namespace RZP\Gateway\Worldline\Mock;

use RZP\Gateway\Worldline;

class AESCrypto extends Worldline\AESCrypto
{
    public function encryptString(string $string)
    {
        return bin2hex(parent::encryptString($string));
    }
}
