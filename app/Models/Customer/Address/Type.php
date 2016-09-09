<?php

namespace RZP\Models\Customer\Address;

use RZP\Exception;

class Type
{
    const CUSTOMER          = 'customer';

    const SHIPPING_ADDRESS  = 'shipping_address';

    protected static $validEntityTypes = [
        self::CUSTOMER
    ];

    protected static $validAddressTypes = [
        self::SHIPPING_ADDRESS
    ];

    public static function validateEntityType($entityType)
    {
        if (in_array($entityType, self::$validEntityTypes) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid entity type for address: ' . $entityType);
        }
    }

    public static function validateAddressType($addressType)
    {
        if (in_array($addressType, self::$validAddressTypes) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid type for address: ' . $addressType);
        }
    }
}