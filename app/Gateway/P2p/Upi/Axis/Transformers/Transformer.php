<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

abstract class Transformer
{
    public $input;

    abstract public function transform(): array;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function put(string $key, $value)
    {
        $this->input[$key] = $value;

        return $this;
    }

    public function toInteger($value)
    {
        $number = intval($value);

        return $number;
    }

    public function toBoolean($value)
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $booleanValue;
    }
}
