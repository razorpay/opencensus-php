<?php

namespace RZP\Gateway\Card\Fss;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Models\Base\UniqueIdEntity as UniqueIdEntity;

class AesCrypto extends Base\AESCrypto
{
    const MODE_CBC  = AES::MODE_CBC;

    public function encryptString(string $str)
    {
        $cipherdText = parent::encryptString($str);

        return UniqueIdEntity::encodeData(base64_encode($cipherdText), 'UTF-8', 'ISO-8859-1');
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(base64_decode($string));
    }
}
