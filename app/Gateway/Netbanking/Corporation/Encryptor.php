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
        $encoded = $this->aes->decrypt(base64_decode($encryptedString));

        $encoded = explode(self::PAIRS_SEPARATOR, $encoded);

        $data = [];

        foreach ($encoded as $value)
        {
            $pair = explode(self::KEY_VALUE_SEPARATOR, $value);

            // Incase the value sent from the gateway does not
            // follow it's own convention and does not add the separator
            if(count($pair) !== 2)
            {
                continue;
            }

            $data[$pair[0]] = $pair[1];
        }

        return $data;
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt($string);
    }
}
