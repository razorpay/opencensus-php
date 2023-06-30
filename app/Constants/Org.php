<?php

namespace RZP\Constants;

/**
 * Keeps Org related constants.
 */
class Org {
    const BUSSINESS_NAME = 'bussiness_name';
    const SECURITY_BRANDING_LOGO = 'security_branding_logo';
    const SHOW_OTP_MSG = 'show_otp_msg';
    const BRANDING_LOGO = 'branding_logo';
    const SHOW_RZP_LOGO = 'show_rzp_logo';

    const ORG_BRANDING = [
        'IN' => [
            self::BUSSINESS_NAME => 'Razorpay',
            self::SECURITY_BRANDING_LOGO => 'https://cdn.razorpay.com/static/assets/pay_methods_branding.png',
            self::SHOW_RZP_LOGO => true,
            self::BRANDING_LOGO => 'https://cdn.razorpay.com/logo.svg',
            self::SHOW_OTP_MSG => true,
        ],
        'MY' => [
            self::BUSSINESS_NAME => 'Curlec',
            self::SECURITY_BRANDING_LOGO => 'https://cdn.razorpay.com/static/assets/i18n/malaysia/security-branding.png',
            self::SHOW_RZP_LOGO => true,
            self::BRANDING_LOGO => 'https://cdn.razorpay.com/static/assets/i18n/malaysia/curlec-light-logo.png',
            self::SHOW_OTP_MSG => false,
        ]
    ];
}
