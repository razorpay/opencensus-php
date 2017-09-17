<?php

namespace RZP\Encryption;

use RZP\Exception;

class Handler
{
    const PGP_ENCRYPTION = 'pgp_encryption';

    const VALID_ENCRYPTION_TYPES = [
        self::PGP_ENCRYPTION,
    ];

    protected $params;

    protected $cipher;

    public function __construct(string $type, array $params)
    {
        $this->params = $params;

        $this->cipher = $this->getCipher($type);
    }

    public function encryptFile(string $filePath)
    {
        $data = file_get_contents($filePath);

        $encryptedData = $this->encrypt($data);

        file_put_contents($filePath, $encryptedData);
    }

    public function encrypt(string $data)
    {
        return $this->cipher->encrypt($data);
    }

    protected function getCipher($type)
    {
        switch ($type)
        {
            case self::PGP_ENCRYPTION :
                 return new PGPEncryption($this->params);

            default:
                throw new Exception\LogicException('Not A Valid Encryption Type');
        }

    }

}
