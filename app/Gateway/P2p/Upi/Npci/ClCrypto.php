<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use phpseclib\Crypt\AES;

class ClCrypto
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function newAes()
    {
        return new AES(AES::MODE_CTR, base64_decode($this->token));
    }

    public function encryptAes256(string $text)
    {
        return base64_encode($this->newAes()->encrypt($text));
    }

    public function decryptAes256(string $text)
    {
        return $this->newAes()->decrypt(base64_decode($text));
    }
}
