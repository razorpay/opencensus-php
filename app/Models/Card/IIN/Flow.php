<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Feature\Constants as Feature;

class Flow
{
    const _3DS              = '3ds';
    const PIN               = 'pin';
    const OTP               = 'otp';
    const IFRAME            = 'iframe';
    const MAGIC             = 'magic';
    const HEADLESS_OTP      = 'headless_otp';
    const IVR               = 'ivr';
    const DCC_BLACKLISTED   = 'dcc_blacklisted';

    // in case of any changes in flows, please contact cards team
    // as the same need to be updated in CPS for Rearch flow as well
    public static $flows = [
        self::_3DS              => 1,
        self::PIN               => 2,
        self::OTP               => 4,
        self::IFRAME            => 8,
        self::MAGIC             => 16,
        self::HEADLESS_OTP      => 32,
        self::IVR               => 64,
        self::DCC_BLACKLISTED   => 128,
    ];

    public static $featureToFlowMappings = [
        Flow::OTP => [
            Feature::AXIS_EXPRESS_PAY  => Flow::OTP,
            Feature::HEADLESS_DISABLE  => Flow::HEADLESS_OTP,
            Feature::IVR_DISABLE       => Flow::IVR,
        ],
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

    public static function isValidFlow($flow)
    {
        if (isset(self::$flows[$flow]) === true)
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
        if (is_array($flows) === false)
        {
            return $flows;
        }

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

    public static function disableFlow($bitmap, $flow)
    {
        if (self::isValidFlow($flow) === false)
        {
            return $bitmap;
        }

        $flowbit = self::$flows[$flow];

        if (($bitmap & $flowbit) === $flowbit)
        {
            $bitmap = ($bitmap & (~$flowbit));
        }

        return $bitmap;
    }

    public static function enableFlow($bitmap, $flow)
    {
        if (self::isValidFlow($flow) === false)
        {
            return $bitmap;
        }

        $flowbit = self::$flows[$flow];

        if ((~$bitmap & $flowbit) === $flowbit)
        {
            $bitmap = ($bitmap | ($flowbit));
        }

        return $bitmap;
    }
}
