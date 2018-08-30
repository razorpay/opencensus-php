<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function encryptString(string $string)
    {
        return base64_encode(parent::encryptString($string));
    }
}
