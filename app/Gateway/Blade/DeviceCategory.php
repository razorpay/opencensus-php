<?php

namespace RZP\Gateway\Blade;

class DeviceCategory
{
    const DESKTOP   = 0;

    const MOBILE    = 1;

    const SMS       = 2;

    const VOICE     = 3;

    public static function getDeviceCategory($platform)
    {
        $platform = strtoupper($platform);

        if (defined(DeviceCategory::class . '::' . $platform))
        {
            return constant(DeviceCategory::class . '::' . $platform);
        }

        return self::DESKTOP;
    }
}
