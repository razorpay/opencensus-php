<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Gateway\Base;
use phpseclib\Crypt\AES;

class AESCrypto extends Base\AESCrypto
{
    public function __construct(string $key, $iv)
    {
        $key = base64_decode($key);

        parent::__construct(AES::MODE_CBC, $key, $iv);
    }

    public function encryptString(string $string)
    {
        $payload = $this->aes->encrypt($string);

        return base64_encode($payload);
    }

    public function decryptString(string $string)
    {
        $payload = base64_decode($string);

        return $this->aes->decrypt($payload);
    }
}
