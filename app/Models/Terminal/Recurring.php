<?php

namespace RZP\Models\Terminal;

class Recurring
{
    // Terminal to be used for non recurring payments
    const NON_RECURRING     = 'non_recurring';

    // Terminal to be used for first recurring transaction
    const RECURRING_3DS     = 'recurring_3ds';

    // Terminal to be used for non-3DS transactions after first succesful payment
    const RECURRING_NON_3DS = 'recurring_non_3ds';

    protected static $types = [
        self::NON_RECURRING,
        self::RECURRING_3DS,
        self::RECURRING_NON_3DS,
    ];

    protected static $bitPosition = [
        self::NON_RECURRING     => 1,
        self::RECURRING_3DS     => 2,
        self::RECURRING_NON_3DS => 3,
    ];

    /**
     * Checks if a particular type of recurring is applicable,
     * by seeing if the corresponding bit position is set.
     * Shift right 'pos' times and check LSB
     * @param  string  $hexType Hex value of the bit-wise field
     * @param  string  $type    Name of the recurring type to be checked
     * @return boolean          Whether type is applicable
     */
    public static function isTypeApplicable($hexType, $type)
    {
        $pos = self::$bitPosition[$type];

        return ((($hexType >> ($pos - 1)) & 1) === 1);
    }
}

