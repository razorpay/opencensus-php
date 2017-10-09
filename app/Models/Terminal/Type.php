<?php

namespace RZP\Models\Terminal;

class Type
{
    // Terminal to be used for non recurring payments
    const NON_RECURRING     = 'non_recurring';

    // Terminal to be used for first recurring transaction
    const RECURRING_3DS     = 'recurring_3ds';

    // Terminal to be used for non-3DS transactions after first succesful payment
    const RECURRING_NON_3DS = 'recurring_non_3ds';

    // Terminal to be used for IVR transactions
    const IVR               = 'ivr';

    protected static $types = [
        self::NON_RECURRING,
        self::RECURRING_3DS,
        self::RECURRING_NON_3DS,
        self::IVR,
    ];

    protected static $bitPosition = [
        self::NON_RECURRING     => 1,
        self::RECURRING_3DS     => 2,
        self::RECURRING_NON_3DS => 3,
        self::IVR               => 4,
    ];

    /**
     * Checks if a particular type of terminal is applicable,
     * by seeing if the corresponding bit position is set.
     * Shift right 'pos' times and check LSB
     * @param  string  $hexType Hex value of the bit-wise field
     * @param  string  $type    Name of the type to be checked
     * @return boolean          Whether type is applicable
     */
    public static function isApplicable($hexType, $type)
    {
        $pos = self::$bitPosition[$type];

        return ((($hexType >> ($pos - 1)) & 1) === 1);
    }

    public static function isApplicableType($types, $type)
    {
        if ((isset($types[$type]) === true) and
            ($types[$type] === '1'))
        {
            return true;
        }

        return false;
    }

    public static function getValidTypes()
    {
        return self::$types;
    }

    public static function getBitPosition($type)
    {
        return self::$bitPosition[$type];
    }

    public static function getEnabledType($hex)
    {
        $types = [];

        foreach (self::$types as $type)
        {
            $pos = self::$bitPosition[$type];
            $value = ($hex >> ($pos - 1)) & 1;

            if ($value)
            {
                array_push($types, $type);
            }
        }

        return $types;
    }

    /**
     * Takes the hex value and merges it
     * with the hex value of the events passed.
     *
     * @param  array    $events
     * @param  integer  $hex
     * @return integer
     */
    public static function getHexValue($types, $hex)
    {
        foreach ($types as $type => $value)
        {
            $pos = Type::getBitPosition($type);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current type.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }
}

