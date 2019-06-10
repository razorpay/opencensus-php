<?php

namespace App\Merchant\Notifications;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'Instant Settlements',
            'description' =>
                'Get your payments settled within a few hours and never have a shortfall of working capital. ',
            'start_ts'    => 1548909000,
            'end_ts'      => 1556728200,
            'icon'        => 'settlements',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Request Access',
                    'url'   => '/settlements#requestearlyaccess',
                ],
            ],
            'filters'     => [
                'experiments'         => ['is_announcement'],
            ],
            'ga'          => [
                'action'        => 'Instant Settlements - Announcement'
            ]
        ],
        [
            'title'       => 'RazorpayX',
            'description' => 'With RazorpayX, track, automate and accelerate every aspect of your financial payouts',
            'start_ts'    => 1545625800,
            'end_ts'      => 1554006600,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/razorx.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get Started',
                    'url'   => '#profile_dropdown',
                ],
            ],
            'filters'     => [
                'tags' => ['announcement_razorpayx'],
            ]
        ],
        [
            'title'       => 'Android SDK Upgrade',
            'description' => 'Your Android SDK needs an update to conform with Google Play\'s new policy. Please update before 9 January 2019 for payments to continue working.',
            'start_ts'    => 1545996600,
            'end_ts'      => 1547094600,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/android.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Download latest SDK now',
                    'url'   => 'https://github.com/razorpay/razorpay-android-sample-app/releases/tag/v1.5.1',
                ],
            ],
            'filters'     => [
                'tags' => ['android_sdk_merchants'],
            ]
        ],
        [
            'title'       => 'Introducing Payment Pages',
            'description' => 'Create custom-branded Payment Pages in minutes to collect payments securely. No integrations or coding required! Check it out now!',
            'start_ts'    => 1550695523,
            'end_ts'      => 1553199300,
            'icon'        => 'https://cdn.razorpay.com/static/assets/paymentpages/display_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Go To Payment Pages',
                    'url'   => '/paymentpages',
                ],
            ],
            'filters'     => [
                'tags' => ['payment_pages_new'],
            ]
        ],
        [
            'title'       => 'All new Payment Pages!',
            'description' => 'Payment pages now has a ton of enhanced features, a lot more customisation and a better look and feel. Check it out now!',
            'start_ts'    => 1550695523,
            'end_ts'      => 1553199300,
            'icon'        => 'https://cdn.razorpay.com/static/assets/paymentpages/display_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Go To Payment Pages',
                    'url'   => '/paymentpages',
                ],
            ],
            'filters'     => [
                'not_tags' => ['payment_pages_new'],
            ]
        ],
        [
            'title'       => 'Razorpay Capital',
            'description' =>
                'Get loans up to Rs 10 Lakhs for your business and repay from your Razorpay settlements with ease. ',
            'start_ts'    => 1551673800,
            'end_ts'      => 1559305800,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/capital.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'I\'m Interested',
                    'url'   => 'https://razorpay.com/capital/?get-in-touch&utm_source=signup&utm_medium=banner&utm_campaign=businessloans_febs2',
                ],
            ],
            'filters'     => [
                'experiments' => ['capital_announcement'],
            ],
            'ga'          => [
                'action' => 'Capital - Announcement',
            ],
        ],
        [
            'title'       => 'Instant Settlements',
            'description' => 'Get all your settlements within the same working day with Razorpay Instant Settlements!',
            'start_ts'    => 1560175200,
            'end_ts'      => 1560794400,
            'icon'        => 'settlements',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Request Access',
                    'url'   => '/settlements#requestearlyaccess',
                ],
            ],
            'filters'     => [
                'experiments'  => ['announcements_early_settlements_1'],
            ],
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}

