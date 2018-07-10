<?php

namespace RZP\Gateway\Mpi\Enstage;

use RZP\Gateway\Mpi\Base\DeviceCategory as Base;

class DeviceCategory extends Base
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
}
