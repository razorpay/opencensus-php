<?php

namespace RZP\Encryption;

use RZP\Exception;

class Handler
{
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

    public function encodeFile(string $filePath)
    {
        $data = file_get_contents($filePath);

        $encodedData = base64_encode($data);

        file_put_contents($filePath, $encodedData);
    }

    public function encrypt(string $data)
    {
        return $this->cipher->encrypt($data);
    }

    protected function getCipher(string $type)
    {
        switch ($type)
        {
            case Type::PGP_ENCRYPTION :
                 return new PGPEncryption($this->params);
            case Type::AES_ENCRYPTION :
                 return new AESEncryption($this->params);
            default:
                throw new Exception\LogicException('Not A Valid Encryption Type');
        }

    }

}
