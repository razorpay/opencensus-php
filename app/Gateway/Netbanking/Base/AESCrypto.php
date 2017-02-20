<?php

namespace RZP\Gateway\Netbanking\Base;

use phpseclib\Crypt\AES;

class AESCrypto
{
    protected $aes;

    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        $this->aes = new AES($mode);

        $this->aes->setKey($masterKey);

        $this->aes->setIV($initializationVector);
    }

    public function encryptString(string $string)
    {
        // returning Encrypted String
        return base64_encode($this->aes->encrypt($string));
    }

    public function decryptString(string $string)
    {
        // returning Decrypted String
        return $this->aes->decrypt(base64_decode($string));
    }
}
