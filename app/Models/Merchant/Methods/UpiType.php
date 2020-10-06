<?php

namespace RZP\Models\Merchant\Methods;

class UpiType
{

    // upi collect
    const COLLECT = 'collect';

    // upi intent
    const INTENT = 'intent';

    const COLLECT_INTENT_BOTH_ENABLED = 3;

    const COLLECT_INTENT_BOTH_DISABLED = 0;

    protected static $types = [
        self::COLLECT,
        self::INTENT,
    ];

    protected static $upiTypeBitPosition = [
        self::COLLECT => 1,
        self::INTENT => 2,
    ];

    public static function getEnabledUpiTypes(int $upi, int $upi_type): array
    {
        $upiTypes = [];

        foreach (self::$upiTypeBitPosition as $upiType => $value)
        {
            if (($upi_type & $value) > 0)
            {
                $upiTypes[$upiType] = $upi;
            }
            else
            {
                $upiTypes[$upiType] = 0;
            }
        }

        return $upiTypes;
    }

    public static function getUpdatedBinaryValue(int $upiType, array $upiTypeArray): int
    {
        foreach ($upiTypeArray as $upiTypeElem => $value)
        {
            $value = (int) $value;

            $bitPosition = self::$upiTypeBitPosition[$upiTypeElem];

            // Set the bit
            if ($value === 1)
            {
                $upiType = $upiType | $bitPosition;
            }
            // Reset the bit
            else
            {
                $upiType = $upiType & (~$bitPosition);
            }
        }

        return $upiType;
    }

    public static function getUpiTypeFromUpi(int $upi):int
    {
        if ($upi === 1)
        {
           return self::COLLECT_INTENT_BOTH_ENABLED;
        }

        return self::COLLECT_INTENT_BOTH_DISABLED;

    }

}

