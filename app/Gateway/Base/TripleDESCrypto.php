<?php

namespace RZP\Gateway\Base;

use phpseclib\Crypt\TripleDES;

class TripleDESCrypto
{
    public function __construct(int $mode, string $masterKey, $padding)
    {
        $this->des = new TripleDES($mode);

        $this->des->setKey($masterKey);

        if ($padding === false)
        {
            $this->des->disablePadding();
        }

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
     * @param $padding
     *
     * @return string
     */
    public function decryptString($ciphertext)
    {
        return $this->des->decrypt($ciphertext);
    }
}