<?php

namespace RZP\Gateway\Netbanking\Axis;

use phpseclib\Crypt\AES;

class Crypto
{
    protected $masterKey;

    protected $iv;

    public function __construct(string $mode)
    {
        $this->masterKey = $this->getGatewayInstance($mode)->getSecret();

        $this->iv = $this->getGatewayInstance($mode)->getSecret();
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

        return $aes;
    }

    protected function getGatewayInstance(string $mode)
    {
        $gateway = new \RZP\Gateway\Netbanking\Axis\Gateway;

        $gateway->setMode($mode);

        return $gateway;
    }
}
