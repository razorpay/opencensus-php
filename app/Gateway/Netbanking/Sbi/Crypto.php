<?php

namespace RZP\Gateway\Netbanking\Sbi;

class Crypto
{
    const IV_SIZE = 16;

    const TAG_LENGTH = 16;

    protected $key;

    protected $iv;

    protected $method;

    protected $tag;

    public function __construct($key, $iv, $method)
    {
        $this->key = $key;

        $this->iv = $iv;

        $this->method = $method;
    }

    public function encrypt($stringToEncrypt)
    {
        $encryptedString = openssl_encrypt(
            $stringToEncrypt,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv,
            $this->tag
        );

        return base64_encode($this->iv . $encryptedString . $this->tag);
    }

    public function decrypt($stringToDecrypt)
    {
        $stringToDecrypt = base64_decode($stringToDecrypt);

        $this->tag = substr($stringToDecrypt, -(self::TAG_LENGTH));

        $stringToDecrypt = substr($stringToDecrypt, self::IV_SIZE, -(self::TAG_LENGTH));

        return openssl_decrypt(
            $stringToDecrypt,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv,
            $this->tag
        );
    }
}
