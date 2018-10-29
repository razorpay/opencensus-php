<?php

namespace RZP\Gateway\Atom;

use phpseclib\Crypt\AES;

class AESCrypto
{
    protected $iv = '000102030405060708090A0B0C0D0E0F';

    protected $password;

    protected $salt;

    public function __construct(string $password, $salt)
    {
        $this->password = $password;

        $this->salt = $salt;
    }

    public function encryptString(string $text)
    {
        $aes = $this->createAesCrypter();

        $b64 = bin2hex($aes->encrypt($text));

        return $b64;
    }

    public function decryptString(string $string)
    {
        $aes = $this->createAesCrypter();

        // returning Decrypted String
        return $aes->decrypt(hex2bin($string));
    }

    public function createAesCrypter()
    {
        $cipher = new AES();

        $cipher->setPassword($this->password, 'pbkdf2', 'sha1', $this->salt, 65536, 32);

        $cipher->setIV(hex2bin($this->iv));

        return $cipher;
    }
}
