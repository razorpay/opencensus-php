<?php

namespace RZP\Models\Plan\Subscription;

class Type
{
    // An amount that is charged in the auth transaction
    const HAS_ADDONS     = 'has_addons';

    // Subscription starts at a later, future time
    const HAS_START_AT   = 'has_start_at';

    protected static $types = [
        self::HAS_START_AT,
        self::HAS_ADDONS,
    ];

    protected static $bitPosition = [
        self::HAS_START_AT     => 1,
        self::HAS_ADDONS => 2,
    ];

    /**
     * Checks if a particular type of subscription is applicable,
     * by seeing if the corresponding bit position is set.
     * Shift right 'pos' times and check LSB
     *
     * @param  string  $hex     Hex value of the bit-wise field
     * @param  string  $type    Name of the type to be checked
     * @return boolean          Whether type is applicable
     */
    public static function isApplicable($hex, $type)
    {
        $pos = self::getBitPosition($type);

        return ((($hex >> ($pos - 1)) & 1) === 1);
    }

    public static function getHexWithTypeMarked($hex, $type, $value)
    {
        $pos = self::getBitPosition($type);

        $value = $value ? 1 : 0;

        // Sets the bit value for the given type.
        $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));

        return $hex;
    }

    public static function getBitPosition(string $event): int
    {
        return self::$bitPosition[$event];
    }
}
