<?php

namespace RZP\Models\User;

class Role
{
    const MANAGER    = 'manager';
    const OPERATIONS = 'operations';
    const FINANCE    = 'finance';
    const SUPPORT    = 'support';
    const ADMIN      = 'admin';
    const SELLERAPP  = 'sellerapp';
    const OWNER      = 'owner';

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
