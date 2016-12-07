<?php

namespace Rzp\Gateway\Netbanking\Axis;

use phpseclib\Crypt\Aes;

trait AesTrait
{
    public function encryptString(string $string, $string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setMasterKey($masterKey);

        return base64_encode($aes->encrypt($string));
    }

    public function decryptString(string $string, $string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setMasterKey($masterKey);

        $encryptedString = base64_decode($string);

        return $aes->decrypt($encryptedString);
    }
}
