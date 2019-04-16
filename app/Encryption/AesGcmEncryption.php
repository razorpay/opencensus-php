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
        $tag = '';

        $encrypted = openssl_encrypt($data, self::CIPHER, $this->secret, OPENSSL_RAW_DATA, $this->iv, $tag);

        return bin2hex($encrypted . $tag);
    }

    public function decrypt(string $data): string
    {
        $data = hex2bin($data);

        $tag = substr($data, -16);

        $data = substr($data, 0, -16);

        return openssl_decrypt($data, self::CIPHER, $this->secret, OPENSSL_RAW_DATA, $this->iv, $tag);
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validateInput('aes_gcm_encryption', $params);
    }
}
