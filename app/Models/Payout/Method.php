<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants\Entity as E;

class Method
{
    const FUND_TRANSFER     = 'fund_transfer';
    const UPI               = 'upi';

    public static $methods = [
        self::FUND_TRANSFER,
        self::UPI,
    ];

    public static $destinationMethodMap = [
        E::BANK_ACCOUNT    => self::FUND_TRANSFER,
        E::CARD            => self::FUND_TRANSFER,
        E::VPA             => self::UPI,
        E::WALLET_ACCOUNT  => self::FUND_TRANSFER,
    ];

    public static function isValid(string $method): bool
    {
        $key = __CLASS__ . '::' . strtoupper($method);

        return ((defined($key) === true) and (constant($key) === $method));
    }

    public static function validateMethod(string $method)
    {
        if (self::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid Payout method: ' . $method);
        }
    }

    public static function getAll(): array
    {
        return self::$methods;
    }
}
