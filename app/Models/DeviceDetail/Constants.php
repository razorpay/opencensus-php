<?php

namespace RZP\Models\DeviceDetail;

class Constants
{
    // onboarding source
    const EASY_ONBOARDING       = 'easy_onboarding';
    const PHANTOM_ONBOARDING    = 'phantom_onboarding';
    const UNBOUNCE              = 'unbounce';

    const CLIENT_IP       = 'ip';
    const G_CLICK_ID      = 'gclid';
    const SERVICE         = 'service';
    const SERVICE_PGOS    = 'pgos';
    const SERVICE_API     = 'api';
    const G_CLIENT_ID     = '_ga';

    const DEVICE                    = 'device';
    const TYPE                      = 'type';

    const ANDROID                   = 'android';
    const IOS                       = 'ios';

    const MOBILE_APP_SOURCES = [
        self::ANDROID,
        self::IOS
    ];
    const I18N_MY_SIGNUP = 'i18n_my_signup';

    const WORKFLOW_TYPE = 'workflow_type';

    const PRODUCT  = "product";

    const PLATFORM = "platform";

    const MOBILE = "mobile";

    const SIGNUP_SOURCE = "signup_source";

    const MODULAR_ONBOARDING = 'MODULAR_ONBOARDING';
}
