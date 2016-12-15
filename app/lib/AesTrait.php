<?php

namespace RZP\Lib;

use phpseclib\Crypt\AES;

trait AesTrait
{
    public function encryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        // returning Encrypted String
        return $aes->encrypt($string);
    }

    public function decryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        // returning Decrypted String
        return $aes->decrypt($string);
    }
}
