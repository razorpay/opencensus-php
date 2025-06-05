<?php

namespace RZP\Signature;

abstract class Signature
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;

        $this->validateParams($params);
    }

    abstract public function sign(string $data): string ;

    abstract protected function validateParams(array $params);
}
