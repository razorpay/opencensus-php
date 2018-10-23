<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Gateway\Base\AESCrypto;

class Encryptor extends AESCrypto
{
    const KEY_VALUE_SEPARATOR = '~';
    const PAIRS_SEPARATOR     = '`';

    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        parent::__construct($mode, $masterKey, $initializationVector);

        // AES encryption with 256 block length
        $this->aes->setKeyLength(128);
        $this->aes->setBlockLength(256);
    }

    public function encryptData(
        array $data,
        $keyValueSeparator = self::KEY_VALUE_SEPARATOR,
        $pairsSeparator = self::PAIRS_SEPARATOR
    )
    {
        $encoded = [];

        foreach ($data as $key => $value)
        {
            $pair = $key . $keyValueSeparator . $value;

            array_push($encoded, $pair);
        }

        $encoded = implode($pairsSeparator, $encoded);

        return base64_encode($this->aes->encrypt($encoded));
    }

    public function decryptAndFormatData(
        string $encryptedString,
        $keyValueSeparator = self::KEY_VALUE_SEPARATOR,
        $pairsSeparator = self::PAIRS_SEPARATOR
    )
    {
        $decryptedString = $this->aes->decrypt(base64_decode($encryptedString));

        $encoded = explode($pairsSeparator, $decryptedString);

        $data = [];

        foreach ($encoded as $value)
        {
            $pair = explode($keyValueSeparator, $value);

            $data[$pair[0]] = $pair[1];
        }

        return $data;
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt($string);
    }
}
