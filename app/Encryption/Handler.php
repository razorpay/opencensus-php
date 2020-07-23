<?php

namespace RZP\Encryption;

use RZP\Exception;

class Handler
{
    protected $params;

    protected $cipher;

    protected $type;

    public function __construct(string $type, array $params)
    {
        $this->params = $params;

        $this->type = $type;

        $this->cipher = $this->getCipher($type);
    }

    public function encryptFile(string $filePath)
    {
        $data = file_get_contents($filePath);

        $encryptedData = $this->encrypt($data);

        file_put_contents($filePath, $encryptedData);
    }

    public function decryptFile(string $filePath)
    {
        $data = file_get_contents($filePath);

        $decryptedData = $this->decrypt($data);

        file_put_contents($filePath, $decryptedData);
    }

    public function encrypt(string $data)
    {
        return $this->cipher->encrypt($data);
    }

    public function decrypt(string $data)
    {
        return $this->cipher->decrypt($data);
    }

    protected function getCipher(string $type)
    {
        switch ($type)
        {
            case Type::PGP_ENCRYPTION :
                 return new PGPEncryption($this->params);
            case Type::AES_ENCRYPTION :
                 return new AESEncryption($this->params);
            case Type::AES_GCM_ENCRYPTION :
                return new AesGcmEncryption($this->params);
            default:
                throw new Exception\LogicException('Not A Valid Encryption Type');
        }

    }

}
