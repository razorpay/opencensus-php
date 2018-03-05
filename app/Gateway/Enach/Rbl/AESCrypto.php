<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function encryptString(string $plainText)
    {
        $cipherdText = parent::encryptString($plainText);

        return base64_encode($cipherdText);
    }

    public function decryptString(string $encodedText)
    {
        $encryptedText = base64_decode($encodedText);

        return parent::decryptString($encryptedText);
    }
}
