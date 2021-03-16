<?php

namespace RZP\Models\Card;

use RZP\Exception;

class Type
{
    const CREDIT      = 'credit';
    const DEBIT       = 'debit';
    const PREPAID     = 'prepaid';
    const UNKNOWN     = 'unknown';

    public static $fundAccountCardTypesInExperiment = [
        self::CREDIT,
        self::PREPAID
    ];

    public static function getType($type, $network = null)
    {
        if ($network === Network::AMEX)
        {
            return self::CREDIT;
        }

        if ($network === Network::MAES)
        {
            return self::DEBIT;
        }

        if (empty($type))
        {
            return self::UNKNOWN;
        }

        self::checkType($type);

        return $type;
    }

    public static function checkType($type)
    {
        if (self::isValidType($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }

    public static function getMaxmindCardType($type)
    {
        if ($type === self::UNKNOWN)
        {
            return 'other';
        }

        return $type . 'card';
    }

    public static function getCardTypes():array
    {
        return [
            self::CREDIT,
            self::DEBIT,
            self::PREPAID
        ];
    }

    public static function isValidFundAccountCardType(string $type = null, string $variant): bool
    {
        if ($variant === 'on')
        {
            return (in_array($type, self::$fundAccountCardTypesInExperiment, true) === true);
        }

        return in_array($type, [self::CREDIT, self::DEBIT], true);
    }
}
