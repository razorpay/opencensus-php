<?php

namespace RZP\Gateway\Upi\Idfc;

use phpseclib\Crypt;

class Crypto
{
    protected $kek = null;

    public function __construct(array $config, $mode = 'test')
    {
        $this->kek = $config["{$mode}_kek"];
        $this->password = $config["{$mode}_password"];
    }

    /**
     * Decrypts the text using KEK
     * @param  $ciphertext ciphertext to decrypt. Base64 encoded
     * @return string decrypt text
     */
    public function decryptUsingKEK($ciphertext)
    {
        // echo $ciphertext . PHP_EOL;
        $ciphertext = base64_decode($ciphertext);

        // echo sha1($ciphertext) . PHP_EOL;

        // int len= b.length;
        // if (len > keyBytes.length) len = keyBytes.length;
        $cipher = $this->getAESInstance();

        return $cipher->decrypt($ciphertext);
    }

    /**
     * Generates the value for the merchant credential block
     * @param  string $transactionId Transaction Id for that Request
     * @param  string $dek           DEK, unencrypted (not encoded either)
     * @return string (base64 encoded)
     */
    public function generateMerchantCredential($transactionId, $dek)
    {
        $text = $transactionId . '#' . $this->password;
        return $this->encrypt($text, $dek);
    }

    /**
     * Encrypts the given plaintext using the given key
     * @param  string $plaintext plaintext to be encrypted
     * @param  string $key Key to use for encryption (not base64-encoded)
     * @return string encrypted text, base64 encoded
     */
    public function encrypt($plaintext, $key)
    {
        $cipher = $this->getAESInstance($key);

        $ciphertext = $cipher->encrypt($plaintext);

        return base64_encode($ciphertext);
    }

    /**
     * Returns a AES Cipher instance
     * to encrypt or decrypt content
     * @param  string $key optional key. KEK is used if this is not provided
     * @return phpseclib\Crypt\AES
     */
    protected function getAESInstance($key = null)
    {
        if ($key === null)
        {
            $key = $this->kek;
        }

        if (strlen($key) > 16)
        {
            $key = substr($key, 0, 16);
        }

        $cipher = new Crypt\AES();
        $cipher->setKey($key);

        /**
         * This is where you cry. Join us at #pg_frust
         * http://www.cryptofails.com/post/70059594911/cakephp-using-the-iv-as-the-key
         */
        $cipher->setIV($key);

        return $cipher;
    }
}
