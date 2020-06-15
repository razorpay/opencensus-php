<?php

namespace RZP\Gateway\Base;

use phpseclib\Crypt\AES;

class AESCrypto
{
    const MODE_CBC = AES::MODE_CBC;
    const MODE_ECB = AES::MODE_ECB;

    protected $aes;

    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        $this->aes = new AES($mode);

        $this->aes->setKey($masterKey);

        $this->aes->setIV($initializationVector);
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
