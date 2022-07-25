<?php

namespace RZP\Models\Card\IIN;

class MandateHub
{
    const MANDATE_HQ      = 'mandate_hq';
    const BILLDESK_SIHUB  = 'billdesk_sihub';
    const RUPAY_SIHUB     = 'rupay_sihub';

    public static $mandate_hubs = [
        self::MANDATE_HQ       => 1,
        self::BILLDESK_SIHUB   => 2,
        self::RUPAY_SIHUB      => 4,
    ];

    public static function getEnabledMandateHubs($hex)
    {
        $mandateHubs = [];

        foreach (self::$mandate_hubs as $mandateHub => $value)
        {
            if (($hex & $value) === $value)
            {
                array_push($mandateHubs, $mandateHub);
            }
        }

        return $mandateHubs;
    }

    public static function getHexValue(array $mandateHubs)
    {
        $hex = 0;

        foreach ($mandateHubs as $mandateHub => $value)
        {
            if ($value === '1')
            {
                $hex = $hex | self::$mandate_hubs[$mandateHub];
            }
        }

        return $hex;
    }

    public static function getValid()
    {
        return array_keys(self::$mandate_hubs);
    }
}

