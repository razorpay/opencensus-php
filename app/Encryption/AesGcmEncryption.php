<?php

namespace RZP\Encryption;

class AesGcmEncryption extends AESEncryption
{
    const TAG = 'tag';

    const CIPHER = 'aes-256-gcm';

    protected $tag;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->tag = $params[self::TAG] ?? '';
    }

    public function encrypt(string $data): string
    {
        return openssl_encrypt($data, self::CIPHER, $this->secret, 0, $this->iv, $this->tag);
    }

    public function decrypt(string $data): string
    {
        return openssl_encrypt($data, self::CIPHER, $this->secret, 0, $this->iv, $this->tag);
    }

    public function getTag()
    {
        return $this->tag;
    }
}
