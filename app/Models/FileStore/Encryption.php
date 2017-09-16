<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Encryption
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

    public function getCipher($type)
    {
        switch ($type)
        {
            case self::PGP_ENCRYPTION:
                 // below is not implemented, needs to be implemented or
                 // used from a library preferably if available
                 return new PGPEncrypter($this->params);

            default:
                throw new Exception\LogicException('Not A Valid Encryption Type');
        }

    }

    public function encryptFile(string $filePath)
    {
        $data = file_get_contents($filePath);

        $encryptedData = $this->cipher->encrypt($data);

        file_put_contents($filePath, $encryptedData);
    }
}
