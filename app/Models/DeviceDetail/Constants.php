<?php

namespace RZP\Models\DeviceDetail;

class Constants
{
    const CLIENT_IP                 = 'ip';
    const G_CLIENT_ID               = '_ga';
    const G_CLICK_ID                = 'gclid';
    const UNBOUNCE                  = 'unbounce';
    const EASY_ONBOARDING           = 'easy_onboarding';
    const PGOS_ONBOARDED_MERCHANT   = 'pgos_onboarded_merchant';

    const DEVICE                    = 'device';
    const TYPE                      = 'type';

    const ANDROID                   = 'android';
    const IOS                       = 'ios';

    const MOBILE_APP_SOURCES = [
        self::ANDROID,
        self::IOS
    ];
}
