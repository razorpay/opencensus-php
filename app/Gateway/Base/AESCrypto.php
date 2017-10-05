<?php

namespace RZP\Gateway\Base;

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

    public function setKeyLength(int $length)
    {
        $this->aes->setKeyLength($length);
    }

    public function encryptString(string $string)
    {
        return $this->aes->encrypt($string);
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt($string);
    }
}
