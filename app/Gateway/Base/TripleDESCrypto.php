<?php

namespace RZP\Gateway\Base;

use phpseclib\Crypt\TripleDES;

class TripleDESCrypto
{
    /**
     * TripleDESCrypto constructor.
     *
     * @param int    $mode
     * @param string $masterKey
     *
     */
    public function __construct(int $mode, string $masterKey, bool $padding)
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
     *
     * @return string
     */
    public function decryptString($ciphertext)
    {
        return $this->des->decrypt($ciphertext);
    }
}
