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

    public static $allRoles = [
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::SUPPORT,
        self::ADMIN,
        self::SELLERAPP,
        self::OWNER,
    ];

    public static $writerRoles = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::ADMIN,
    ];

    public static $readerRoles = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::ADMIN,
        self::SELLERAPP,
    ];

    public static $allExceptSellerRole = [
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::SUPPORT,
        self::ADMIN,
        self::OWNER,
    ];

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
