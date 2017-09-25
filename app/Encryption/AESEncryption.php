<?php

namespace RZP\Encryption;

use RZP\Exception;
use RZP\Gateway\Base;

class AESEncryption extends Encryption
{
    const SECRET = 'secret';
    const IV = 'iv';
    const MODE = 'mode';

    protected $secret;
    protected $iv;
    protected $mode;

    protected $encryptor;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->iv = $params[self::IV] ?? '';

        $this->encryptor = new Base\AESCrypto($params[self::MODE], $params[self::SECRET], $this->iv);
    }

    public function encrypt(string $data): string
    {
        return $this->encryptor->encryptString($data);
    }

    public function decrypt(string $data): string
    {
        return $this->encryptor->decryptString($data);
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validateInput('aes_encryption', $params);
    }
}
