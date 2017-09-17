<?php

namespace RZP\Encryption;

class PGPEncryption implements IEncryption
{
    const SECRET    = 'secret';

    protected $secret = null;

    public function __construct($params)
    {
        // TODO : add validations
        $this->secret = $params[self::SECRET];
    }

    public function encrypt(string $data) : string
    {
        $res = gnupg_init();

        gnupg_addencryptkey($res, $this->secret);

        $enc = gnupg_encrypt($res, $data);

        return $enc;
    }

    public function decrypt(string $data) : string
    {
        // TODO
    }
}