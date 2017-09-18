<?php

namespace RZP\Encryption;

class PGPEncryption implements IEncryption
{
    const SECRET    = 'secret';

    protected $secret = null;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->secret = $params[self::SECRET];
    }

    public function encrypt(string $data) : string
    {
        $res = gnupg_init();

        gnupg_addencryptkey($res, $this->secret);

        $enc = gnupg_encrypt($res, $data);

        if ($enc === false)
        {
            //throw exception
        }

        return $enc;
    }

    public function decrypt(string $data) : string
    {
        // TODO
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validate('pgpEncryption', $params);
    }
}
