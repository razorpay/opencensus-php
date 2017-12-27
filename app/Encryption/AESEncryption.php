<?php

namespace RZP\Encryption;

use RZP\Exception;
use RZP\Gateway\Base;

class AESEncryption extends Encryption
{
    const SECRET         = 'secret';
    const IV             = 'iv';
    const MODE           = 'mode';

    protected $secret;

    protected $iv;

    protected $mode;

    protected $encryptor;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->iv = $params[self::IV] ?? '';

        $this->mode = $params[self::MODE];

        $this->secret = $params[self::SECRET];

        $this->encryptor = new Base\AESCrypto($this->mode, $this->secret, $this->iv);
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
