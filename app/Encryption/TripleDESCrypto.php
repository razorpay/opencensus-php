<?php

namespace RZP\Encryption;

use phpseclib\Crypt\TripleDES;

class TripleDESCrypto
{
    const MODE_ECB = TripleDES::MODE_ECB;
    /**
     * TripleDESCrypto constructor.
     *
     * @param int     $mode
     * @param string  $masterKey
     * @param boolean $padding
     *
     */
    public function __construct(int $mode, string $masterKey, bool $padding = true)
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
    public function encrypt($plaintext)
    {
        return $this->des->encrypt($plaintext);
    }

    /**
     * Decrypts string.
     * @param $ciphertext
     *
     * @return string
     */
    public function decrypt($ciphertext)
    {
        return $this->des->decrypt($ciphertext);
    }
}
