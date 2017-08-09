<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Gateway\Base;

use phpseclib\Crypt\AES;

class AESCrypto extends Base\AESCrypto
{
    const IV = '1234567890123456';

    protected $aes;

    protected $iv;

    protected $masterKey;

    public function __construct(string $key)
    {
        $this->masterKey = base64_decode($key);

        $this->iv = self::IV;

        $this->createAesCrypter();
    }

    public function encryptString(string $string)
    {
        return base64_encode($this->aes->encrypt($string));
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt(base64_decode($string));
    }

    protected function createAesCrypter()
    {
        $this->aes = new AES(AES::MODE_CBC);

        $this->aes->setKey($this->masterKey);

        $this->aes->setIV($this->iv);
    }
}
