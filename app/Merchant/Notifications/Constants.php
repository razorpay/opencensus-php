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
                    'url'   => '/config#instantrefunds',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/refunds/#setting-the-default-speed-of-refunds',
                ]
            ]
        ],
        [
            'title'       => 'Instant Refund Update',
            'description' => 'The pricing for instant refund has been revised. Now, you can process refunds instantly for Debit Cards too along with Credit Cards, UPI & Net Banking.',
            'start_ts'    => 1599489000,
            'end_ts'      => 1604092722,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/instant-refunds.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'What’s Changing?',
                    'url'   => '/config#debitrefund',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Check Coverage',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/refunds/supported-payment-methods/#debit-cards',
                ]
            ],
            'filters'     => [
                'experiments'  => ['ir_pricing_v2_rollout_1','ir_pricing_v2_rollout_2','ir_pricing_v2_rollout_3','ir_pricing_v2_rollout_4'],
            ]
                ],
        [
            'title'       => 'Instant Refund Update',
            'description' => 'The pricing for instant refund has been revised. Now, you can process refunds instantly for Debit Cards too along with Credit Cards, UPI & Net Banking.',
            'start_ts'    => 1592485521,
            'end_ts'      => 1597089243,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/instant-refunds.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Check Pricing',
                    'url'   => '/config#instantfee',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Check Coverage',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/refunds/supported-payment-methods/#debit-cards',
                ]
                ],
                'filters'     => [
                    'experiments'  => ['instant_refunds_default_pricing_v2'],
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
        [
            'title'       => '2 Step Verification',
            'description' => 'Enable 2 step verification with SMS based OTP along with user credentials to add additional security to your account.',
            'start_ts'    => 1588876200,
            'end_ts'      => 1590172140,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/2fa.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => '/profile',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/my-account/#set-up-two-factor-authentication',
                ]
            ],
        ],
        [
            'title'       => 'Payments Capture',
            'description' => 'Enable auto capturing of your payments and have a better control of your payment system.',
            'start_ts'    => 1593581451,
            'end_ts'      => 1606800651,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/Capture_Settings.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Configure Now',
                    'url'   => '/config',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/payments/capture-settings/',
                ]
            ],
        ],
        [
            'title'       => 'RazorpayX Payout Link',
            'description' => 'Send money easily and instantly without Bank Account Details.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/rx-payout.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Explore',
                    'url'   => 'https://razorpay.com/x/payout-links/?utm_source=pgdashboard&utm_medium=annoucement&utm_campaign=payoutlinks',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn More',
                    'url'   => 'https://razorpay.com/blog/payout-links-e-commerce-companies-automate-cod-refunds?utm_source=pgdashboard&utm_medium=annoucement&utm_campaign=payoutlinks',
                ],
            ],
            'start_ts'    => 1591727400,
            'end_ts'      => 1592764199,
            'filters'     => [
                'business_type' => MerchantDetails\BusinessType::REGISTERED_BUSINESS_TYPES,
            ]
        ],
        [
            'title'       => 'Introducing UPI AutoPay',
            'description' => 'Powered by Razorpay Subscriptions, your customers can now set up recurring payments using their UPI App.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/autopay.svg',
            'track_event' => true,
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get Early Access',
                    'url'   => ''
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/upi-autopay/#dshbrdntf',
                ],
            ],
            'start_ts'    => 1595935137,
            'end_ts'      => 1600940643,
        ],
        [
            'title'       => 'Yes! You\'re pre-approved for a loan.',
            'description' => 'Get Working Capital Loans from top NBFCs within 2 days!',
            'start_ts'    => 1597343400,
            'end_ts'      => 1602700200,
            'track_event' => true,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/capital-loans.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Apply for your offer',
                    'url'   => '/capital/loans',
                ],
            ],
            'filters'     => [
                'experiments'         => ['capital_loans_announcement_aug2020'],
            ]
        ],
        [
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your transaction fee to 1.65%.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => 'https://lp.razorpay.com/razorpayxca-anncment',
                ],
            ],
            'start_ts'    => 1597390475,
            'end_ts'      => 1601445430,
            'filters'     => [
                'experiments'         => ['project_nitro'],
            ],
        ],
        [
            'title'       => 'Introducing Payment Buttons',
            'description' => 'Start accepting payments on your website or blog in less than 5 minutes. No coding needed.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-button.svg',
            'track_event' => true,
            'id'          => 'paymentButton_GTM',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/paymentbuttons/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://betasite.razorpay.com/docs/pb-index-true/payment-button',
                ]
            ],
            'start_ts'    => 1598941800,
            'end_ts'      => 1601555400,
            'filters'     => [
                'activated' => 1,
            ]
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
