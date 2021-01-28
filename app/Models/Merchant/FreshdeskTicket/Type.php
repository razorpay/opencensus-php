<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class Type
{
    const RESERVE_BALANCE_ACTIVATE = 'reserve_balance_activate';
    const SUPPORT_DASHBOARD        = 'support_dashboard';
    const SUPPORT_DASHBOARD_X      = 'support_dashboard_x';

    public static function exists(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
