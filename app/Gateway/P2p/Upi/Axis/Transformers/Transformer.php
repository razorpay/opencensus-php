<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;

abstract class Transformer
{
    public $input;

    public $action;

    abstract public function transform(): array;

    public function __construct(array $input, string $action = null)
    {
        $this->input  = $input;
        $this->action = $action;
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
        return intval(round(floatval($value) * 100));
    }

    public function toTimestamp($now = null)
    {
        return Carbon::parse($now)->getTimestamp();
    }

    public function toUsernameHandle($value)
    {
        $vpa = explode(Vpa\Entity::AEROBASE, $value);

        return [
            Vpa\Entity::USERNAME    => $vpa[0],
            Vpa\Entity::HANDLE      => $vpa[1] ?? null,
        ];
    }

    /**
     * get handle from vpa
     *
     * @param $vpa
     * @return string
     */
    public function getVpaHandle($vpa)
    {
        return explode(Vpa\Entity::AEROBASE, $vpa)[1];
    }
}
