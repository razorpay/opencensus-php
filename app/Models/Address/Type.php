<?php

namespace RZP\Models\Address;

use RZP\Exception;
use RZP\Constants\Entity;

class Type
{
    const CUSTOMER          = 'customer';
    const PAYMENT           = 'payment';
    const TOKEN             = 'token';
    const STAKEHOLDER       = 'stakeholder';
    const RAW_ADDRESS       = 'raw_address';
    const CONTACT           = 'contact';

    const SHIPPING_ADDRESS  = 'shipping_address';
    const BILLING_ADDRESS   = 'billing_address';
    const RESIDENTIAL       = 'residential';

    protected static $validEntityTypes = [
        self::CUSTOMER,
        self::PAYMENT,
        self::TOKEN,
        self::STAKEHOLDER,
        self::RAW_ADDRESS,
        self::CONTACT,
    ];

    protected static $validTypes = [
        self::CUSTOMER => [
            self::SHIPPING_ADDRESS,
            self::BILLING_ADDRESS,
        ],
        self::PAYMENT  => [
            self::BILLING_ADDRESS,
        ],
        self::TOKEN  => [
            self::BILLING_ADDRESS,
        ],
        self::STAKEHOLDER => [
            self::RESIDENTIAL,
        ],
        self::RAW_ADDRESS => [
            self::SHIPPING_ADDRESS,
        ],
        self::CONTACT => [
            self::SHIPPING_ADDRESS,
            self::BILLING_ADDRESS,
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

        //$entity = 'RZP\\Models\\' . ucfirst($entityType) . '\\Entity';

        $entity = Entity::getEntityClass($entityType);

        return $entity;
    }

    public static function getValidTypes(string $entityType)
    {
        self::validateEntityType($entityType);

        return self::$validTypes[$entityType];
    }
}
