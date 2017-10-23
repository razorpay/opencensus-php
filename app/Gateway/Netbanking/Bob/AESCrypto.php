<?php

namespace RZP\Gateway\Netbanking\Bob;

use RZP\Gateway\Base;

class AESCrypto extends Base\AESCrypto
{
    const VALUE_SEPARATOR = '=';
    const PAIR_SEPARATOR  = '|';

    public function __construct(int $mode, string $masterKey, string $initializationVector = '')
    {
        parent::__construct($mode, $masterKey, $initializationVector);

        $this->aes->setKeyLength(128);

        $this->aes->setBlockLength(128);
    }

    public function encryptData(array $data)
    {
        $formattedData = urldecode(http_build_query($data, '', self::PAIR_SEPARATOR));

        return base64_encode($this->encryptString($formattedData));
    }

    public function decryptData(string $input)
    {
        $input = base64_decode($input);

        return $this->decodeData($this->decryptString($input));
    }

    protected function decodeData(string $input)
    {
        $exploded = explode(self::PAIR_SEPARATOR, $input);

        $output = [];

        foreach ($exploded as $value)
        {
            $pair = explode(self::VALUE_SEPARATOR, $value);

            if (count($pair) !== 2)
            {
                continue;
            }

            $output[$pair[0]] = $pair[1];
        }

        return $output;
    }
}
