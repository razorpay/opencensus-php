<?php

namespace RZP\Gateway\Blade;

class DeviceCategory
{
    const DESKTOP   = 'desktop';
    const MOBILE    = 'mobile';
    const SMS       = 'sms';
    const VOICE     = 'voice';

    const DEVICE_CATEGORY = [
        self::DESKTOP => 0,
        self::MOBILE  => 1,
        self::SMS     => 2,
        self::VOICE   => 3,
    ];

    const AVAILABLE_DEVICE = [
        self::DESKTOP,
        self::MOBILE,
        self::SMS,
        self::VOICE
    ];

    public static function getDeviceCategory(string $platform = null)
    {
        $platform = strtolower($platform);

        if (in_array($platform, self::AVAILABLE_DEVICE, true) === true)
        {
            return self::DEVICE_CATEGORY[$platform];
        }

        return self::DEVICE_CATEGORY[self::DESKTOP];
    }
}
