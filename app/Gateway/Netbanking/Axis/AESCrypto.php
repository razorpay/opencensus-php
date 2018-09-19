<?php

namespace RZP\Gateway\Netbanking\Axis;

use phpseclib\Crypt\AES;

class AESCrypto
{
    protected $masterKey;

    protected $iv;

    public function __construct(string $masterKey)
    {
        $this->masterKey = $masterKey;

        $this->iv = $masterKey;
    }

    public function encryptString(string $string)
    {
        $aes = $this->createAesCrypter();

        // returning Encrypted String
        return base64_encode($aes->encrypt($string));
    }

    public function decryptString(string $string)
    {
        $aes = $this->createAesCrypter();

        // returning Decrypted String
        return $aes->decrypt(base64_decode($string));
    }

    protected function createAesCrypter()
    {
        $aes = new AES(Constants::MODE_CBC);

        $aes->setKey($this->masterKey);

        $aes->setIV($this->iv);

        $aes->setKeyLength(128);

        return $aes;
    }
}
