<?php

namespace RZP\Encryption;

class AesGcmEncryption extends Encryption
{
    const CIPHER = 'aes-256-gcm';

    const SECRET         = 'secret';
    const IV             = 'iv';

    protected $secret;

    protected $iv;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->iv = $params[self::IV] ?? '';

        $this->secret = $params[self::SECRET];
    }

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

    protected function validateParams(array $params)
    {
        (new Validator)->validateInput('aes_gcm_encryption', $params);
    }
}
