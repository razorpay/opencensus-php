<?php

namespace RZP\Gateway\Mpi\Base;

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

        if (in_array($platform, static::AVAILABLE_DEVICE, true) === true)
        {
            return static::DEVICE_CATEGORY[$platform];
        }

        return static::DEVICE_CATEGORY[static::DESKTOP];
    }
}
