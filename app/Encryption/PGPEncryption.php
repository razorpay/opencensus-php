<?php

namespace RZP\Encryption;

class PGPEncryption extends IEncryption
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
            throw new Exception\LogicException('PGP Encryption Failed');
        }

        return $enc;
    }

    public function decrypt(string $data) : string
    {
        $res = gnupg_init();

        gnupg_adddecryptkey($res, $this->secret);

        $dec = gnupg_decrypt($res, $data);

        if ($dec === false)
        {
            throw new Exception\LogicException('PGP Decryption Failed');
        }

        return $dec;
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validate('pgp_encryption', $params);
    }
}
