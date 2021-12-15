<?php

namespace RZP\Models\P2p\Client;

use RZP\Exception\BadRequestValidationFailureException;

class Config extends ArrayAttribute
{
    const APP_FULL_NAME     = 'app_full_name';
    const VPA_SUFFIX        = 'vpa_suffix';
    const SMS_SENDER        = 'sms_sender';
    const MAX_VPA           = 'max_vpa';
    const SMS_SIGNATURE     = 'sms_signature';
    const APP_COLLECT_LINK  = 'app_collect_link';

    protected $map = [
        self::APP_FULL_NAME     => 1,
        self::VPA_SUFFIX        => 1,
        self::SMS_SENDER        => 1,
        self::MAX_VPA           => 1,
        self::SMS_SIGNATURE     => 1,
        self::APP_COLLECT_LINK  => 1,
    ];
}
