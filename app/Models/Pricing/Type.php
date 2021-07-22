<?php

namespace RZP\Models\Pricing;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    //
    // By default, all the rules are of type pricing
    // commission type pricing is used in partners to specify partner fixed commission or explicit commission
    //
    const PRICING    = 'pricing';
    const COMMISSION = 'commission';
    const BUY_PRICING = 'buy_pricing';

    protected static $types = [
        self::PRICING,
        self::COMMISSION,
        self::BUY_PRICING,
    ];

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    /**
     * @param $type
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Invalid type: ' . $type);
        }
    }

    public static function getTypes()
    {
        return self::$types;
    }
}
