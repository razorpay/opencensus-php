<?php

namespace RZP\Encryption;

abstract class Encryption
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;

        $this->validateParams($params);
    }

    abstract public function encrypt(string $data): string ;

    abstract public function decrypt(string $data): string ;

    abstract protected function validateParams(array $params);
}
