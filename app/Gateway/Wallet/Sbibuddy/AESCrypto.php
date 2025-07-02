<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Base;
use RZP\Models\Base\UniqueIdEntity as UniqueIdEntity;

class AESCrypto extends Base\AESCrypto
{
    public function encryptString(string $data)
    {
        $encoded = UniqueIdEntity::encodeData($data, 'UTF-8', 'ISO-8859-1');

        $encryptedData = parent::encryptString($encoded);

        return base64_encode($encryptedData);
    }

    public function decryptString(string $data)
    {
        $decoded = base64_decode($data);

        $decryptedData = parent::decryptString($decoded);

        return UniqueIdEntity::encodeData($decryptedData, 'ISO-8859-1', 'UTF-8');
    }
}
