<?php

namespace RZP\Mail\Base;

class Common
{
    const SUPPORT       = 'support';
    const SCORECARD     = 'scorecard';
    const REFUNDS       = 'refunds';
    const SETTLEMENTS   = 'settlements';
    const INVOICES      = 'invoices';
    const NOTIFICATIONS = 'notifications';
    const REPORTS       = 'reports';
    const CARE          = 'care';
    const ERRORS        = 'errors';
    const DEVELOPERS    = 'developers';
    const ALERTS        = 'alerts';
    const EMI           = 'emi';

    const MAIL_ADDRESSES = [
        self::SUPPORT       => 'support@razorpay.com',
        self::SCORECARD     => 'scorecard@razorpay.com',
        self::REFUNDS       => 'refunds@razorpay.com',
        self::SETTLEMENTS   => 'settlements@razorpay.com',
        self::INVOICES      => 'invoices@arzorpay.com',
        self::NOTIFICATIONS => 'notifications@razorpay.com',
        self::REPORTS       => 'reports@razorpay.com',
        self::CARE          => 'care@razorpay.com',
        self::ERRORS        => 'errors@razorpay.com',
        self::DEVELOPERS    => 'developers@razorpay.com',
        self::ALERTS        => 'alerts@razorpay.com',
        self::EMI           => 'emifiles@razorpay.com'
    ];

    const FROM_HEADER = [
        self::SUPPORT   => 'Team Razorpay',
        self::SCORECARD => 'Razorpay Scorecard',
        self::REFUNDS   => 'Refunds File',
        self::REPORTS   => 'Team Razorpay',
        self::CARE      => 'Team Razorpay',
        self::ALERTS    => 'Razorpay Webhook Support'
    ];
}
