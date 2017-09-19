<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    public function encryptString(string $data)
    {
        $encoded = utf8_encode($data);

        $encryptedData = parent::encryptString($encoded);

        return base64_encode($encryptedData);
    }

    public function decryptString(string $data)
    {
        $decoded = base64_decode($data);

        $decryptedData = parent::decryptString($decoded);

        return utf8_decode($decryptedData);
    }
}
