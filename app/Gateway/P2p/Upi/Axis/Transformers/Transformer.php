<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Vpa;

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

    public function toPaisa($value)
    {
        return round(floatval($value) * 100);
    }

    public function toUsernameHandle($value)
    {
        $vpa = explode(Vpa\Entity::AEROBASE, $value);

        return [
            Vpa\Entity::USERNAME    => $vpa[0],
            Vpa\Entity::HANDLE      => $vpa[1] ?? null,
        ];
    }
}
