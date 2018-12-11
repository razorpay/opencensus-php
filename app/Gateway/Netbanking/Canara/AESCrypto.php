<?php

namespace RZP\Gateway\Netbanking\Canara;

use RZP\Gateway\Base;
use RZP\Constants\Mode;

class AESCrypto extends Base\AESCrypto
{
    public function __construct($config)
    {
        $masterKey = $config['key'];

        $iv = $config['IV'];

        parent::__construct(Constants::MODE_CBC, $masterKey, $iv);
    }

    public function encryptString(string $string)
    {
        return strtoupper(bin2hex(parent::encryptString($string)));
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt(hex2bin($string));
    }
}
