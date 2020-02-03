<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class Type
{
    const RESERVE_BALANCE_ACTIVATE = 'reserve_balance_activate';

    public static function exists(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
