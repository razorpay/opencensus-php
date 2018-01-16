<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants;

class Method
{
    const FUND_TRANSFER  = 'fund_transfer';

    protected static $methods = [
        self::FUND_TRANSFER     => 'Fund Transfer',
    ];

    protected static $methodToEntityMap = [
        self::FUND_TRANSFER     => Constants\Entity::BANK_ACCOUNT
    ];

    public static function formatted($method)
    {
        return self::$methods[$method];
    }

    public static function getAllPayoutMethods()
    {
        return array_keys(self::$methods);
    }

    public static function validateMethod($method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payout method: ' . $method);
        }
    }

    public static function getEntityClass($method)
    {
        $name = self::getEntityName($method);

        $class = Constants\Entity::getEntityClass($name);

        return $class;
    }

    public static function getEntityName(string $method): string
    {
        return self::$methodToEntityMap[$method];
    }
}
