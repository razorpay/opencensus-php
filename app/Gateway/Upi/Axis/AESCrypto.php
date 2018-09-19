<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
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
