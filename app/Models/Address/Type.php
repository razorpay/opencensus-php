<?php

namespace RZP\Models\Address;

use RZP\Exception;

class Type
{
    const CUSTOMER          = 'customer';

    const SHIPPING_ADDRESS  = 'shipping_address';

    protected static $validEntityTypes = [
        self::CUSTOMER
    ];

    protected static $validAddressTypes = [
        self::CUSTOMER => [
            self::SHIPPING_ADDRESS
        ]
    ];

    public static function validateEntityType($entityType)
    {
        if (in_array($entityType, self::$validEntityTypes) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid entity type for address: ' . $entityType);
        }
    }

    public static function validateAddressType($addressType, $entityType)
    {
        if (in_array($addressType, self::$validAddressTypes[$entityType]) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid type for address: ' . $addressType);
        }
    }

    public static function getSetterFunctionForAddress($addressType)
    {
        return 'set' . studly_case($addressType) . 'Id';
    }

    public static function getEntityClass($entityType)
    {
        self::validateEntityType($entityType);

        $entity = 'RZP\\Models\\' . ucfirst($entityType) . '\\Entity';

        return $entity;
    }
}