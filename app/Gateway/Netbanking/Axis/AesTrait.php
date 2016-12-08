<?php

namespace Rzp\Gateway\Netbanking\Axis;

use phpseclib\Crypt\Aes;

trait AesTrait
{
    public function encryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        return base64_encode($aes->encrypt($string));
    }

    public function decryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        $encryptedString = base64_decode($string);

        return $aes->decrypt($encryptedString);
    }
}
