<?php

namespace RZP\Gateway\Fss;

use RZP\Gateway\Base\TripleDESCrypto as BaseTripeDESCrypto;

class TripleDESCrypto extends BaseTripeDESCrypto
{
    /**
     * Encrypts String
     * back fills with some ASCII value to complete the block before encryption.
     * @param $plaintext
     *
     * @return string
     */
    public function encryptString($plaintext)
    {
        $encryptedData = parent::encryptString($plaintext);
        return bin2hex($encryptedData);
    }

    /**
     * Decrypts String
     * Instead of removing the extra padded string, FSS is attaching '^' as a End of data.
     * so after decrypting data truncating the last extra padded part.
     * @param $ciphertext
     *
     * @return string
     */
    public function decryptString($ciphertext)
    {
        $data = hex2bin($ciphertext);

        $decryptedData = parent::decryptString($data);

        if (empty(strpos($decryptedData, '^')) === false)
        {
            $decryptedData = substr($decryptedData, 0, (strpos($decryptedData, '^') - 1));
        }

        $decryptedData = rtrim($decryptedData, "\0");

        return $decryptedData;
    }
}