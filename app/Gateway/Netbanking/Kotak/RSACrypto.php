<?php

namespace RZP\Gateway\Netbanking\Kotak;

use phpseclib\Crypt\RSA;

class RSACrypto
{
    protected $masterKey;

    public function __construct(string $masterKey)
    {
        $this->masterKey = $masterKey;

        $this->rsa = $this->createRsaCrypter();
    }

    public function encryptString(string $string)
    {
        $str = $this->rsa->encrypt($string);

        return urlencode(base64_encode($str));
    }

    public function decryptString(string $string)
    {
        $str = $this->urlSafeBase64Decode(urldecode($string));

        return $this->rsa->decrypt($str);
    }

    function urlSafeBase64Decode($string)
    {
        // Bank sends the response in the callback where they change '+' to '-' and '/' to '_' to make it url safe

        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }

    protected function createRsaCrypter()
    {
        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

        $rsa->setMGFHash('sha1');

        $rsa->setHash('sha256');

        $rsa->loadKey($this->masterKey);

        return $rsa;
    }
}
