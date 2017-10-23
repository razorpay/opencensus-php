<?php

namespace RZP\Mail\Base;

class Constants
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
    const ADMIN         = 'admin';
    const ACTIVATION    = 'activation';
    const SUBSCRIPTIONS = 'subscriptions';
    const IRCTC         = 'irctc';

    const MAIL_ADDRESSES = [
        self::SUPPORT       => 'support@razorpay.com',
        self::SCORECARD     => 'scorecard@razorpay.com',
        self::REFUNDS       => 'refunds@razorpay.com',
        self::SETTLEMENTS   => 'settlements@razorpay.com',
        self::INVOICES      => 'invoices@razorpay.com',
        self::SUBSCRIPTIONS => 'subscriptions@razorpay.com',
        self::NOTIFICATIONS => 'notifications@razorpay.com',
        self::REPORTS       => 'reports@razorpay.com',
        self::CARE          => 'care@razorpay.com',
        self::ERRORS        => 'errors@razorpay.com',
        self::DEVELOPERS    => 'developers@razorpay.com',
        self::ALERTS        => 'alerts@razorpay.com',
        self::EMI           => 'emifiles@razorpay.com',
        self::ADMIN         => 'admin@razorpay.com',
        self::ACTIVATION    => 'activationsteam@razorpay.com',
        self::IRCTC         => 'support@razorpay.com',
    ];

    const HEADERS = [
        self::SUPPORT     => 'Team Razorpay',
        self::SCORECARD   => 'Razorpay Scorecard',
        self::REFUNDS     => 'Refunds File',
        self::SETTLEMENTS => 'Settlements File',
        self::INVOICES    => 'Razorpay Invoices',
        self::REPORTS     => 'Team Razorpay',
        self::CARE        => 'Team Razorpay',
        self::ALERTS      => 'Razorpay Webhook Support',
        self::ACTIVATION  => 'Razorpay Activations Team',
        self::IRCTC       => 'Razorpay IRCTC Refunds',
    ];
}
