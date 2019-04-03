<?php

namespace RZP\Gateway\Netbanking\Kotak;

use phpseclib\Crypt\AES;

class AESCrypto
{
    protected $masterKey;

    protected $iv = '0123456789ABCDEF';

    public function __construct(string $masterKey)
    {
        $this->masterKey = base64_decode($masterKey);

        $this->aes = $this->createAesCrypter();
    }

    public function encryptString(string $string)
    {
        $newStr = $this->iv . $string;

        // returning Encrypted String
        return base64_encode($this->aes->encrypt($newStr));
    }

    public function decryptString(string $string)
    {
        $str = base64_decode($string);

        $iv = substr($str, 0, 16);

        $this->aes->setIV($iv);

        $newStr = substr($str, 16);

        return $this->aes->decrypt($newStr);
    }

    protected function createAesCrypter()
    {
        $aes = new AES(AES::MODE_CBC);

        $aes->setKey($this->masterKey);

        $aes->setIV($this->iv);

        return $aes;
    }
}
