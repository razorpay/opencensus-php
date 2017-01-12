<?php

namespace RZP\Gateway\Netbanking\Icici;

use phpseclib\Crypt\AES;

class AESCrypto
{
    protected $masterKey;

    protected $aes;

    public function __construct(string $masterKey)
    {
        $this->masterKey = $masterKey;

        $this->createAesCrypt();
    }

    public function encryptString(string $string)
    {
        return $this->aes->encrypt($string);
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt($string);
    }

    protected function createAesCrypt()
    {
        $this->aes = new AES(Constants::MODE_ECB);

        $this->aes->setKey($this->masterKey);
    }
}
