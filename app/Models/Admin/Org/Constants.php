<?php

namespace RZP\Models\Admin\Org;

class Constants
{
    const RAZORPAY    = 'Razorpay';

    const RZP         = '100000razorpay';

    const DEVSERVE_HOST_URL = 'dashboard.dev.razorpay.in';

    const DEVSERVE_CURLEC_HOST_URL = 'dashboard-curlec.dev.razorpay.in';

    const ALLOW_TO_BUSINESS_BANKING = [
        self::RZP
    ];

    // list of possible 2FA auth modes
    const EMAIL         = 'email';
    const SMS           = 'sms';
    const SMS_AND_EMAIL = 'sms_and_email';

    const DEFAULT_MAX_WRONG_2FA_ATTEMPTS = 9;
}
