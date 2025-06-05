<?php

namespace RZP\Signature;

use RZP\Exception\LogicException;

class PfxSignature extends Signature {

    const PUBLIC_KEY  = 'public_key';
    const PRIVATE_KEY  = 'private_key';

    protected $publicKey;
    protected $privateKey;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->publicKey = $params[self::PUBLIC_KEY] ?? null;

        $this->privateKey = $params[self::PRIVATE_KEY] ?? null;
    }

    public function sign(string $data): string
    {
        if (empty($this->privateKey)) {
            throw new \LogicException('Private key is not set in environment variables.');
        }

        $privateKeyResource = openssl_pkey_get_private($this->privateKey);
        if ($privateKeyResource === false) {
            throw new \LogicException('Invalid private key.');
        }

        $signature = '';
        if (openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256) === false) {
            throw new \LogicException('Unable to sign the content.');
        }

        return $signature;
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validateInput('pfx_signature', $params);
    }
}
