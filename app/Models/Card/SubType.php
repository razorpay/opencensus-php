<?php

namespace RZP\Models\Card;

use RZP\Exception;

class SubType
{
    const CONSUMER    = 'consumer';
    const BUSINESS    = 'business';
    const PREMIUM     = 'premium';

    /**
     * CONSUMER is enabled for all merchant by default
     */
    const DEFAULT_CARD_SUBTYPE = [
        self::CONSUMER                    => 1,
        self::BUSINESS                    => 1,
    ];

    protected static $subTypeBitPosition = [
        self::CONSUMER                    => 1,
        self::BUSINESS                    => 2,
        self::PREMIUM                     => 4,
    ];

    public static function checkSubType($subtype)
    {
        if (self::isValidSubType($subtype) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid sub_type: ' . $subtype);
        }
    }

    public static function isValidSubType($subtype)
    {
        return (array_key_exists($subtype, self::$subTypeBitPosition));
    }

    /**
     * Iterates through subtypes and returns the hex value to be stored
     */
    public static function getHexValue(array $subtypes): int
    {
        $hex = 0;

        foreach ($subtypes as $subtype => $value)
        {
            $value = (int) $value;

            $bitPosition = self::$subTypeBitPosition[$subtype];

            // Set the bit
            if ($value === 1)
            {
                $hex = $hex | $bitPosition;
            }
            // Reset the bit
            else
            {
                $hex = $hex & (~$bitPosition);
            }
        }

        return $hex;
    }

    public static function getEnabledCardSubTypes(int $hex): array
    {
        $subTypes = [];

        foreach (self::$subTypeBitPosition as $subtype => $value)
        {
            $subTypes[$subtype] = (($hex & $value) > 0) ? 1 : 0;
        }

        return $subTypes;
    }
}
