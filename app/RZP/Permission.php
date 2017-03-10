<?php

namespace App\RZP;

class Permission extends Entity
{
    const VIEW_MERCHANT_LOGIN             = 'view_merchant_login';

    public static $adminPermission = [
        'admin_merchant_login'           => [self::VIEW_MERCHANT_LOGIN],
    ];
}
