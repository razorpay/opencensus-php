<?php

namespace App\RZP;

class Permission extends Entity
{
    const VIEW_MERCHANT_LOGIN             = 'view_merchant_login';
    const VIEW_ALL_ENTITY                 = 'view_all_entity';
    const EDIT_PAYMENT_CAPTURE            = 'edit_payment_capture';

    public static $adminPermission = [
        'admin_merchant_login'           => [self::VIEW_MERCHANT_LOGIN],
        'admin_fetch_entity'             => [self::VIEW_ALL_ENTITY],
        'admin_payment_capture'          => [self::EDIT_PAYMENT_CAPTURE],
    ];
}
