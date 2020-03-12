<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use RZP\Models\P2p\Device\Entity;

class ClInput
{
    const CL_VERSION            = 'cl_version';
    const CL_TOKEN              = 'cl_token';
    const CL_EXPIRY             = 'cl_expiry';

    const DEVICE_ID             = 'deviceId';
    const APP_ID                = 'appId';
    const MOBILE                = 'mobile';

    // Other fields which are passed as input for multiple actions

    public static $allowed = [
        self::CL_TOKEN              => 'string|max:1000',
        self::CL_EXPIRY             => 'epoch',
        self::DEVICE_ID             => 'string|max:100',
        self::APP_ID                => 'string|max:100',
        self::MOBILE                => 'string|max:10',
    ];
}
