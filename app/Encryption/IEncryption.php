<?php

namespace RZP\Encryption;

abstract class IEncryption
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;

        $this->validateParams($params);
    }

    abstract public function encrypt(string $data): string ;

    abstract public function decrypt(string $data): string ;

    protected function validateParams(array $params)
    {
        // Should be implemented in child class
    }
}
