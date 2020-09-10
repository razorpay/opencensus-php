<?php

namespace RZP\Models\Merchant\Methods;

class EmiType
{
    // Remove these once the dashboard contract changes
    const NONE_ENABLED = 0;

    const CREDIT_ENABLED = 1;

    const DEBIT_ENABLED = 2;

    const CREDIT_DEBIT_ENABLED = 3;

    // Credit card EMI
    const CREDIT    = 'credit';

    // Debit card emi
    const DEBIT     = 'debit';

    protected static $types = [
        self::CREDIT,
        self::DEBIT,
    ];

    protected static $bitPosition = [
        self::CREDIT => 1,
        self::DEBIT  => 2,
    ];

    public static function isTypeEnabled($types, $type)
    {
        // Uncomment after DB migration
        if (empty($types) === false)
        {
            return true;
        }

        // Uncomment after DB migration
        return false;

        if ((isset($types[$type]) === true) and
            ($types[$type] === '1'))
        {
            return true;
        }

        return false;
    }

    public static function getBitPosition($type)
    {
        return self::$bitPosition[$type];
    }

    public static function getEnabledTypes($hex)
    {
        // Uncomment after DB migration
        if ($hex !== 0)
        {
            return self::$types;
        }
        // Uncomment after DB migration
        return [];

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
            $pos = EmiType::getBitPosition($type);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current type.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }
}
