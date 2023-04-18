<?php

namespace RZP\Models\Order;

use RZP\Exception\BadRequestValidationFailureException;

class ProductType
{
    const INVOICE            = 'invoice';
    const AUTH_LINK          = 'auth_link';
    const PAYMENT_PAGE       = 'payment_page';
    const PAYMENT_HANDLE     = 'payment_handle';
    const PAYMENT_LINK       = 'payment_link';
    const PAYMENT_LINK_V2    = 'payment_link_v2';
    const SUBSCRIPTION       = 'subscription';
    const VIRTUAL_ACCOUNT    = 'virtual_account';
    const PAYMENT_BUTTON     = 'payment_button';
    const PAYMENT_STORE      = 'payment_store';


    public static function isTypeValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function checkType(string $type)
    {
        if (self::isTypeValid($type) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid product type: ' . $type);
        }
    }

    public static function IsForNocodeApps($productType): bool
    {
        return $productType === self::PAYMENT_STORE;
    }
}
