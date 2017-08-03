<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use phpseclib\Crypt\AES;

class Encryptor
{
    protected $masterKey;

    protected $blockSize = 8;

    protected $method = 'aes-128-ecb';

    public function __construct($masterKey)
    {
        $this->aes = new AES(MCRYPT_MODE_ECB);

        $this->aes->setKey($masterKey);

        $this->aes->setKeyLength(128);
    }

    public function setBlockSize($blockSize)
    {
        $this->blockSize = $blockSize;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function encrypt($data)
    {
        $encoded = utf8_encode($data);

        $encryptedData = $this->aes->encrypt($encoded);

        return base64_encode($encryptedData);
    }

    public function decrypt($data)
    {
        $decoded = base64_decode($data);

        $decryptedData = $this->aes->decrypt($decoded);

        return utf8_decode($decryptedData);
    }

    public static function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    public static function pkcs5Unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});

        if ($pad > strlen($text))
        {
            return false;
        }

        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }

        return substr($text, 0, -1 * $pad);
    }
}
