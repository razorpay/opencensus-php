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

    const ALL_ROLES = [
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::SUPPORT,
        self::ADMIN,
        self::SELLERAPP,
        self::OWNER,
    ];

    const WRITER_ROLES = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::ADMIN,
    ];

    const READER_ROLES = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::ADMIN,
        self::SELLERAPP,
    ];

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function allExceptSellerRole()
    {
        return array_diff(self::ALL_ROLES, [self::SELLERAPP]);
    }
}
