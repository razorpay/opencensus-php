<?php

namespace RZP\Encryption;

use RZP\Exception;
use phpseclib\Crypt\AES;
use RZP\Gateway\Wallet\Sbibuddy\AESCrypto;

class AESEncryption extends Encryption
{
    const SECRET = 'secret';
    const IV = 'iv';

    protected $secret;
    protected $iv;

    protected $encryptor;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->secret = $params[self::SECRET] ?? null;

        $this->iv = $params[self::IV] ?? null;

        $this->encryptor = new AESCrypto(AES::MODE_CBC, $this->secret, $this->iv);
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
