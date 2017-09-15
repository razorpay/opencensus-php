<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Gateway\Base\AESCrypto;

class Encryptor extends AESCrypto
{
    const KEY_VALUE_SEPARATOR = '~';
    const PAIRS_SEPARATOR     = '`';

    public function encryptData(array $data)
    {
        $encoded = [];

        foreach ($data as $key => $value)
        {
            $pair = $key . self::KEY_VALUE_SEPARATOR . $value;

            array_push($encoded, $pair);
        }

        $encoded = implode(self::PAIRS_SEPARATOR, $encoded);

        return base64_encode($this->aes->encrypt($encoded));
    }

    public function decryptData(string $encryptedString)
    {
        $decryptedString = $this->aes->decrypt(base64_decode($encryptedString));

        $encoded = explode(self::PAIRS_SEPARATOR, $decryptedString);

        $data = [];

        foreach ($encoded as $value)
        {
            $pair = explode(self::KEY_VALUE_SEPARATOR, $value);

            $data[$pair[0]] = $pair[1];
        }

        return $data;
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt($string);
    }
}
