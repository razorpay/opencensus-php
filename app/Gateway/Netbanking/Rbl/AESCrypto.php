<?php

namespace RZP\Gateway\Netbanking\Rbl;

use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Base;

class AESCrypto extends Base\AESCrypto
{
    const KEY_LENGTH = 256;


    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        $key = $this->getKey($masterKey);

        parent::__construct($mode, $key);
    }

    public function encryptString(string $string)
    {
        return urlencode(base64_encode(parent::encryptString($string)));
    }

    public function decryptString(string $string)
    {
        return parent::decryptString(base64_decode(urldecode($string)));
    }

    protected function getKey($key)
    {
        $keyBytes = (int) self::KEY_LENGTH / 8;

        $keyLength = strlen($key);

        $repeatFactor = (int) $keyBytes / $keyLength;

        return str_repeat($key, $repeatFactor);
    }
}
