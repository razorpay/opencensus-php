<?php

namespace RZP\Models\Settlement;

use RZP\Models\Currency\Currency;

class Constants {
    const LOGO_URL = 'logo_url';

    const SHOW_VIEW_SETTLEMENG_GUIDE_OPTION = 'settelement_guide';

    const SHOW_UTR = 'utr';

    const SHOW_ACC_NO = 'acc_no';

    const RAISE_REQUEST_ON_MAIL = 'raise_req_on_email';

    const RAISE_REQUEST_REDIRECT_LINK = 'raise_req_redirect';

    const CURRENCY_LOGO = 'currency_logo';

    const ORG_NAME = 'org_name';

    const ORG_DATA = [
        'MY' => [
            self::LOGO_URL => 'https://cdn.razorpay.com/static/assets/curlec/logo_invert.png',
            self::SHOW_VIEW_SETTLEMENG_GUIDE_OPTION => false,
            self::SHOW_ACC_NO => false,
            self::SHOW_UTR => false,
            self::RAISE_REQUEST_ON_MAIL => true,
            self::RAISE_REQUEST_REDIRECT_LINK => 'success@curlec.com',
            self::CURRENCY_LOGO => Currency::SYMBOL[Currency::MYR],
            self::ORG_NAME => 'Curlec',
        ],
        'IN' => [
            self::LOGO_URL => 'https://cdn.razorpay.com/logo_invert.png',
            self::SHOW_VIEW_SETTLEMENG_GUIDE_OPTION => true,
            self::SHOW_UTR => true,
            self::SHOW_ACC_NO => true,
            self::RAISE_REQUEST_ON_MAIL => false,
            self::RAISE_REQUEST_REDIRECT_LINK => 'https://dashboard.razorpay.com/#/app/dashboard#request',
            self::CURRENCY_LOGO => Currency::SYMBOL[Currency::INR],
            self::ORG_NAME => 'Razorpay'
        ]
    ];
}
