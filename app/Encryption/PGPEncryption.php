<?php

namespace RZP\Encryption;

use RZP\Exception;

class PGPEncryption extends IEncryption
{
    const SECRET      = 'secret';
    const PUBLIC_KEY  = 'public_key';
    const PRIVATE_KEY = 'private_key';

    protected $secret = null;
    protected $publicKey;
    protected $privateKey;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->secret = $params[self::SECRET];

        if (isset($params[self::PUBLIC_KEY]) === true)
        {
            $this->publicKey = $params[self::PUBLIC_KEY];
        }

        if (isset($params[self::PRIVATE_KEY]) === true)
        {
            $this->privateKey = $params[self::PRIVATE_KEY];
        }
    }

    public function encrypt(string $data) : string
    {
        $res = gnupg_init();

        gnupg_import($res, $this->publicKey);

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

        gnupg_import($res, $this->privateKey);

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
        (new Validator)->validateInput('pgp_encryption', $params);
    }
}
