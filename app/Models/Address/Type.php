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

    protected static $validTypes = [
        self::CUSTOMER => [
            self::SHIPPING_ADDRESS
        ]
    ];

    public static function validateEntityType($entityType)
    {
        if (in_array($entityType, self::$validEntityTypes, true) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid entity type for address: ' . $entityType);
        }
    }

    public static function validateType($type, $entityType)
    {
        if (in_array($type, self::$validTypes[$entityType], true) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid type for address: ' . $type);
        }
    }

    public static function getSetterFunctionForAddress($type)
    {
        return 'set' . studly_case($type) . 'Id';
    }

    public static function getEntityClass($entityType)
    {
        self::validateEntityType($entityType);

        $entity = 'RZP\\Models\\' . ucfirst($entityType) . '\\Entity';

        return $entity;
    }
}