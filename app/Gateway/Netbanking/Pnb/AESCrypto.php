<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Gateway\Netbanking\Base;

class AESCrypto extends Base\AESCrypto
{
    /**
     * Note :
     * The key provided by bank was in the form of a binary file.
     * In order to store the key, we read the file &
     * stored string in a base64 encoded format.
     * To be able to use it, we will have to decode the string.
     */

    public function encryptString(string $string)
    {
        return bin2hex(parent::encryptString(urldecode($string)));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(hex2bin($string));
    }
}
