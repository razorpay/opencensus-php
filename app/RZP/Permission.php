<?php

namespace App\RZP;

class Permission extends Entity
{
    const ADMIN_MERCHANT_LOGIN              = 'admin_merchant_login';

    public static $adminPermission = [
        'admin_merchant_login'           => [self::ADMIN_MERCHANT_LOGIN],
    ];
}
