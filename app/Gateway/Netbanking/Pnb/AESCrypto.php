<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Gateway\Netbanking\Base;

use phpseclib\Crypt\AES;

class AESCrypto extends Base\AESCrypto
{
    /**
     * Note :
     * The key provided by bank was in the form of a binary file.
     * In order to store the key, we read the file &
     * stored string in a base64 encoded format.
     * To be able to use it, we will have to decode the string.
     * This is done in the constructor while setting $masterKey
     */

    const IV = '1234567890123456';

    protected $aes;

    protected $iv;

    protected $masterKey;

    public function __construct(string $masterKey)
    {
        $this->masterKey = base64_decode($masterKey);

        $this->iv = self::IV;

        $this->createAesCrypter();
    }

    public function encryptString(string $string)
    {
        return base64_encode($this->aes->encrypt(urldecode($string)));
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
