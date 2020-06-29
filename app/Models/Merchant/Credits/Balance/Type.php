<?php

namespace RZP\Models\Merchant\Credits\Balance;

class Type
{
    const REWARD_FEE = 'reward_fee';

    public static function exists(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return (defined($key) === true);
    }
}
