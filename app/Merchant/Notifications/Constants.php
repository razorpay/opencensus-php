<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

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
            'end_ts'      => 1561984469,
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
            'start_ts'    => 1560870052,
            'end_ts'      => 1561485608,
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
        [
            'title'       => 'Help us Create a Better Checkout',
            'description' => 'Help us in making the payment experience better for you by answering a few simple questions on Razorpay Flash Checkout. We value your opinion and this would definitely help us serve you better',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/survey.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Give Feedback',
                    'url'   => 'https://razorpay.typeform.com/to/JKyCd0',
                    'url_query_params' => ['mid', 'business_name']
                ],
            ],
            'start_ts'    => 1561981341,
            'end_ts'      => 1567338141,
            'filters'     => [
                'experiments'  => ['checkout_survey'],
            ],
        ],
        [
            'title'       => 'Enable Daily Settlements',
            'description' => 'Get your payments settled on the same working day automatically! Avoid cash-flow issues and prepare better for working capital needs.',
            'start_ts'    => 1573187366,
            'end_ts'      => 1577791088,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/early-settlement.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Daily Settlements',
                    'url'   => '/settlements#automaticsettle',
                ],
            ],
            'filters'     => [
                'features'         => ['es_on_demand'],
            ]
        ],
        [
            'title'       => 'Free Credit Score!',
            'description' => 'Click Here to get your credit score along with the credit report for FREE!',
            'start_ts'    => 1571898702,
            'end_ts'      => 1577791088,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/badge.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get Free Credit Report',
                    'url'   => '/dashboard#creditscore',
                ],
            ],
            'filters'     => [
                'features'  => ['show_credit_score'],
                'role'  => ['owner'],
            ],
        ],
        [
            'title'       => 'Introducing RazorpayX',
            'description' => 'Vendor and customer payouts are now just a click away with RazorpayX',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/razorpayx.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Login Now',
                    'url'   => 'https://x.razorpay.com',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn More',
                    'url'   => 'https://razorpay.com/x/payouts/',
                ],
            ],
            'start_ts'    => 1580099400,
            'end_ts'      => 1580495399,
            'filters'     => [
                'business_type' => MerchantDetails\BusinessType::REGISTERED_BUSINESS_TYPES,
            ]
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
