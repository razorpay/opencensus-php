<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

class Encryptor
{
    protected $masterKey;

    protected $blockSize = 8;

    protected $method = 'AES-128-ECB';

    public function __construct($masterKey)
    {
        $this->masterKey = $masterKey;
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
        $padded = self::pkcs5Pad($data, $this->blockSize);

        $encryptedData = openssl_encrypt($padded, $this->method, $this->masterKey, OPENSSL_ZERO_PADDING);

        return base64_encode($encryptedData);
    }

    public function decrypt($data)
    {
        $decoded = base64_decode($data);

        $decryptedData = openssl_decrypt($decoded, $this->method, $this->masterKey, OPENSSL_ZERO_PADDING);

        return self::pkcs5Unpad($decryptedData);
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
