<?php

namespace RZP\Models\Batch\Processor;

use App;
use phpseclib\Crypt\AES;
use RZP\Constants\HashAlgo;

class AESCrypto
{
    protected $password;

    protected $crypto;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $config = $app['config']->get('applications.batch');

        $this->password = $config['aes_key'];

        $this->crypto = $this->createAesCrypter();
    }

    public function createAesCrypter()
    {
        $cipher = new AES();

        // default hash is sha1
        $cipher->setPassword($this->password);

        return $cipher;
    }

    public function encryptString(string $text)
    {
        $b64 = bin2hex($this->crypto->encrypt($text));

        return $b64;
    }

    public function decryptString(string $string)
    {
        // returning Decrypted String
        return $this->crypto->decrypt(hex2bin($string));
    }
}
