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
            'title'       => 'Instant Refund!',
            'description' => 'Do not make your customers wait for 5-7 days for a refund. Retain customers and improve trust by issuing refunds instantly.',
            'start_ts'    => 1584356700,
            'end_ts'      => 1588960422,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/instant-refunds.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => '/refunds#instantrefunds',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/instant-refunds/',
                ]
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
        [
            'title'       => 'Rev-Up Chennai',
            'description'       => 'Last 30 passes for Razorpay merchants for RevUp Chennai - claim yours today!',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'I\'m in!',
                    'url'   => 'https://hubs.ly/H0m-tv10',
                ],
            ],
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/events.svg',
            'start_ts'    => 1581964200,
            'end_ts'      => 1582309740,
            'filters'     => [
                'experiments'  => ['rev_up_chennai_announcement'],
            ]
        ],
        [
            'title'       => 'SSL Certificate Update for Razorpay API',
            'description' => 'We’re updating the SSL certificate for api.razorpay.com on 18th March, 2020. To understand if this update affects you, click on the link below.',
            'start_ts'    => 1584037800,
            'end_ts'      => 1584642599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/attention.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Read more',
                    'url'   => 'https://razorpay.com/links/update-tls-ssl-certificate',
                ],
            ],
            'filters'     => [
                'activated' => 1,
            ]
        ],
        [
            'title'       => 'Instant Settlement!',
            'description' => 'Now get your settlements instantly 24x7 - even on
            holidays! Avoid cash-flow issues and to boost your working capital!',
            'start_ts'    => 1585548503,
            'end_ts'      => 1596175703,
            'icon'        => 'settlements',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Go to Settlements',
                    'url'   => '/settlements',
                ],
            ],
            'filters'     => [
                'features' => ['es_on_demand'],
                'role'  => ['owner'],
            ]
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
