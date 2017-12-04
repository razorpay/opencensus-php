<?php

namespace RZP\Gateway\Base;

use phpseclib\Crypt\TripleDES;

class TripleDESCrypto
{
    public function __construct(int $mode, string $masterKey)
    {
        $this->des = new TripleDES($mode);

        $this->des->setKey($masterKey);
    }

    /**
     * Encrypts string
     * @param $plaintext
     *
     * @return string
     */
    public function encryptString($plaintext)
    {
        return $this->des->encrypt($plaintext);
    }

    /**
     * Decrypts string.
     * @param $ciphertext
     *
     * @return string
     */
    public function decryptString($ciphertext)
    {
        $this->des->disablePadding();

        return $this->des->decrypt($ciphertext);
    }
}