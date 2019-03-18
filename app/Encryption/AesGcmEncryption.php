<?php

namespace RZP\Encryption;

class AesGcmEncryption extends AESEncryption
{
    const CIPHER = 'aes-256-gcm';

    public function encrypt(string $data): string
    {
        $encrypted = openssl_encrypt($data, self::CIPHER, $this->secret, 0, $this->iv, $tag);

        return $encrypted . $tag;
    }

    public function decrypt(string $data): string
    {
        $tag = substr($data, -16);

        $data = substr($data, 0, -16);

        return openssl_decrypt($data, self::CIPHER, $this->secret, 0, $this->iv, $tag);
    }
}
