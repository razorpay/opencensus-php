<?php

// Work on this in your free time

namespace RZP\Gateway\Netbanking\Icici;

use phpseclib\Crypt\AES;

trait AesTrait
{
    public function encryptString($string, $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        // returning Encrypted String
        return base64_encode($aes->encrypt($string));
    }

    public function decryptString($string, $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        $encryptedString = base64_decode($string);

        // returning Decrypted String
        return $aes->decrypt($encryptedString);
    }
}
