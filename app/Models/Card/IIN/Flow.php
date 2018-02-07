<?php

namespace RZP\Models\Card\IIN;

class Flow
{
    const _3DS      = 1;
    const DEBIT_PIN = 2;
    const OTP       = 4;
    const IFRAME    = 8;

    protected static $flows = [
        '3ds'       => self::_3DS,
        'debit_pin' => self::DEBIT_PIN,
        'otp'       => self::OTP,
    ];

    /**
     * Checks if a particular card flow is applicable,
     * by seeing if the corresponding bit position is set.
     *
     * @param  string  $hexType Hex value of the bit-wise field
     * @param  string  $flows   Bitvalue of flows
     * @return boolean          Whether flows are applicable
     */
    public static function isApplicable($hexType, $flows)
    {
        return (($hexType & $flows) === $flows);
    }

    public static function isApplicableFlow($flows, $flow)
    {
        if ((isset($flows[$flow]) === true) and
            ($flows[$flow] === '1'))
        {
            return true;
        }

        return false;
    }

    public static function getValid()
    {
        return array_keys(self::$flows);
    }

    public static function getEnabledFlows($hex)
    {
        $flows = [];

        foreach (self::$flows as $flow => $value)
        {
            if (($hex & $value) === $value)
            {
                array_push($flows, $flow);
            }
        }

        return $flows;
    }

    /**
     * Takes the hex value and merges it
     * with the hex value of the flows passed.
     *
     * @param  array $flows
     * @param  int   $hex
     * @return int
     */
    public static function getHexValue($flows)
    {
        $hex = 0;

        foreach ($flows as $flow => $value)
        {
            if ($value === '1')
            {
                $hex = $hex | self::$flows[$flow];
            }
        }

        return $hex;
    }
}

