<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use RZP\Gateway\Upi\Axis;

class AESCrypto extends Axis\AESCrypto
{
    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }
}
