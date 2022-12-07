<?php

namespace RZP\Models\DeviceDetail;

class Constants
{
    const EASY_ONBOARDING = 'easy_onboarding';
    const UNBOUNCE = 'unbounce';
    const CLIENT_IP = 'ip';
    const G_CLICK_ID = 'gclid';
    const G_CLIENT_ID = '_ga';

    const DEVICE = 'device';
    const TYPE = 'type';

    const ANDROID   = 'android';
    const IOS       = 'ios';

    const MOBILE_APP_SOURCES = [
        self::ANDROID,
        self::IOS
    ];
}
