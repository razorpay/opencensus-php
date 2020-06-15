<?php

namespace RZP\Gateway\Upi\Mindgate;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base\AESCrypto;

/**
 * Mindgate Crypto class. See docs
 * for explanation
 *
 * AES-ECB mode with no IV
 */
class Crypto extends AESCrypto
{
    public function __construct(string $key, $mode = AES::MODE_ECB)
    {
        // The key is provided and stored as hex-encoded
        $key = hex2bin($key);

        parent::__construct($mode, $key);
    }

    /**
     * Encrypts content as per mindgate
     * @param  string $plaintext
     * @return string ciphertext in uppercase Hex
     */
    public function encrypt(string $plaintext)
    {
        $ciphertext = $this->encryptString($plaintext);
        return strtoupper(bin2hex($ciphertext));
    }

    /**
     * Decrypts responses from the Mindgate API
     * @param  string $data
     * @return string
     */
    public function decrypt(string $ciphertext)
    {
        return $this->decryptString(hex2bin($ciphertext));
    }

    public function setIV(string $iv)
    {
        $this->aes->setIV(hex2bin($iv));

        return $this;
    }

    public function enablePadding()
    {
        $this->aes->enablePadding();

        return $this;
    }
}
