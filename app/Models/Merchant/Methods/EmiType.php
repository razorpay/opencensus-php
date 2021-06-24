<?php

namespace RZP\Models\Merchant\Methods;

class EmiType
{
    const DEFAULT_TYPES = [];

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

    public static function getEmiTypes($hex)
    {
        $emiTypes = [];

        $types = self::getEnabledTypes($hex);

        foreach (self::$types as $type)
        {
            $emiTypes[$type] = in_array($type, $types);
        }

        return $emiTypes;
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
