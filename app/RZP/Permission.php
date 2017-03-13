<?php

namespace App\RZP;

class Permission extends Entity
{
    const VIEW_MERCHANT_LOGIN             = 'view_merchant_login';
    const VIEW_ALL_ENTITY                 = 'view_all_entity';

    public static $adminPermission = [
        'admin_merchant_login'           => [self::VIEW_MERCHANT_LOGIN],
        'admin_fetch_entity'             => [self::VIEW_ALL_ENTITY],
    ];
}
