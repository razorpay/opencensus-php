<?php

namespace RZP\Models\Terminal;

class Type
{
    // Terminal to be used for non recurring payments
    const NON_RECURRING     = 'non_recurring';

    // Terminal to be used for first recurring transaction
    const RECURRING_3DS     = 'recurring_3ds';

    // Terminal to be used for non-3DS transactions after first successful payment
    const RECURRING_NON_3DS = 'recurring_non_3ds';

    // Terminal to be used for IVR transactions
    const IVR               = 'ivr';

    // Terminal to be used to create second recurring payments without 2fa
    const NO_2FA            = 'no_2fa';

    // Terminal to be used for UPI pay
    const PAY               = 'pay';

    // Terminal to be used for ATM PIN transactions
    const PIN               = 'pin';

    // Terminals For Bharat Qr payments
    const BHARAT_QR         = 'bharat_qr';

    // Terminals to be used for Debit recurring
    const DEBIT_RECURRING   = 'debit_recurring';

    // Terminals to be used for Moto payments
    const MOTO              = 'moto';

    // Terminal to be used for Collect UPI payments
    const COLLECT           = 'collect';

    protected static $types = [
        self::NON_RECURRING,
        self::RECURRING_3DS,
        self::RECURRING_NON_3DS,
        self::IVR,
        self::NO_2FA,
        self::PAY,
        self::PIN,
        self::BHARAT_QR,
        self::DEBIT_RECURRING,
        self::MOTO,
        self::COLLECT,
    ];

    protected static $bitPosition = [
        self::NON_RECURRING     => 1,
        self::RECURRING_3DS     => 2,
        self::RECURRING_NON_3DS => 3,
        self::IVR               => 4,
        self::NO_2FA            => 5,
        self::PAY               => 6,
        self::PIN               => 7,
        self::BHARAT_QR         => 8,
        self::DEBIT_RECURRING   => 9,
        self::MOTO              => 10,
        self::COLLECT           => 11,
    ];

    /**
     * Checks if a particular type of terminal is applicable,
     * by seeing if the corresponding bit position is set.
     * Shift right 'pos' times and check LSB
     *
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

    public static function getEnabledTypes($hex)
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
     * @param  array $types
     * @param  int   $hex
     * @return int
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

