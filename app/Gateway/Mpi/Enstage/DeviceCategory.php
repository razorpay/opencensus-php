<?php

namespace RZP\Gateway\Mpi\Enstage;

class DeviceCategory
{
    const DESKTOP           = 'desktop';
    const MOBILE_BROWSER    = 'mobile_browser';
    const MOBILE_APP        = 'mobile_app';


    const DEVICE_CATEGORY = [
        self::MOBILE_APP      => '1',
        self::MOBILE_BROWSER  => '2',
        self::DESKTOP         => '3',
    ];

    const AVAILABLE_DEVICE = [
        self::DESKTOP,
        self::MOBILE_BROWSER,
        self::MOBILE_APP,
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
