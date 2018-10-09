<?php

namespace RZP\Gateway\Upi\Yesbank;

use RZP\Gateway\Base;
use phpseclib\Crypt\AES;

class Crypto extends Base\AESCrypto
{
    public function __construct($secret)
    {
        parent::__construct(AES::MODE_ECB, $secret);
    }
    /*
     * Disabled padding because bank sends encrypted message using AES PKCS5 MODE ECB - not available in PHP,
     * this is a fix
     */
    public function decryptString(string $string)
    {
        $this->aes->disablePadding();

        return parent::decryptString(base64_decode($string));
    }
}
