<?php

namespace RZP\Encryption;

use RZP\Exception;

class PGPEncryption extends Encryption
{
    const PUBLIC_KEY  = 'public_key';
    const PRIVATE_KEY = 'private_key';
    const PASSPHRASE  = 'passphrase';

    protected $publicKey;
    protected $privateKey;
    protected $passphrase = '';

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->publicKey = $params[self::PUBLIC_KEY] ?? null;

        $this->privateKey = $params[self::PRIVATE_KEY] ?? null;

        $this->passphrase = $params[self::PASSPHRASE] ?? null;
    }

    public function encrypt(string $data) : string
    {
        $res = gnupg_init();

        $imp = gnupg_import($res, $this->publicKey);

        gnupg_addencryptkey($res, $imp['fingerprint']);

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

        $imp = gnupg_import($res, $this->privateKey);

        gnupg_adddecryptkey($res, $imp['fingerprint'], $this->passphrase);

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
