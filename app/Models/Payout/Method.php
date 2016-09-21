<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants;

class Method
{
    const FUND_TRANSFER  = 'fund_transfer';

    protected static $methods = array(
        self::FUND_TRANSFER       => 'Fund Transfer',
    );

    protected static $methodToEntityMap = array(
        self::FUND_TRANSFER     => Constants\Entity::BANK_ACCOUNT
    );

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
        if (defined(__CLASS__.'::'.strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payout method: ' . $method);
        }
    }

    public static function getEntityClass($method)
    {
        $class = Constants\Entity::getEntityClass(self::$methodToEntityMap[$method]);

        return $class;
    }
}