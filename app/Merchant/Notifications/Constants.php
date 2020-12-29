<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'The Payments Mobile App is Live!',
            'description' => 'Track payments, create payment links and issue refunds from anywhere with the new Payments mobile app. Get the mobile app now.',
            'start_ts'    => 1608229800,
            'end_ts'      => 1610994599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payments-mobile-app.svg',
            'track_event' => true,
            'id'          => 'Payments-Mobile-App',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Download on iOS',
                    'url'   => 'https://apps.apple.com/in/app/razorpay-payments-dashboard/id1497250144',
                ],
                [
                    'type'  => 'button',
                    'label' => 'Download on Android',
                    'url'   => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                ]
            ],
            'filters'     => [
                'activation_status' => ['activated'],
                'role'  => ['owner', 'admin', 'manager', 'operations'],
            ],
        ],
        [
            'title'       => 'Pay your vendors in seconds',
            'description' => 'Pay upto 300 vendor invoices free every month.',
            'start_ts'    => 1608633480,
            'end_ts'      => 1624358771,
            'icon'        => 'https://cdn.razorpay.com/pg_announcement_icon.svg',
            'track_event' => true,
            'id'          => 'DEC20-VP-C1',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => 'https://x.razorpay.com/vendor-payments',
                ]
            ],
            'filters'     => [
                'experiments'         => ['show_rx_vp_announcement_2'],
            ],
        ],
        [
            'title'       => 'Pay your vendors in seconds',
            'description' => 'Upload invoices and pay vendors and TDS automatically.',
            'start_ts'    => 1604904751,
            'end_ts'      => 1612084863,
            'icon'        => 'https://cdn.razorpay.com/pg_announcement_icon.svg',
            'track_event' => true,
            'id'          => 'NOV20-VP-C1',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => 'https://x.razorpay.com/vendor-payments',
                ]
            ],
            'filters'     => [
                'experiments'         => ['show_rx_vp_announcement'],
            ],
        ],
        [
            'title'       => '2 Step Verification',
            'description' => 'Enable 2 step verification with SMS based OTP along with user credentials to add additional security to your account.',
            'start_ts'    => 1588876200,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/2fa.svg',
            'track_event' => true,
            'id'          => 'TwoStepVerification2020',
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
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your transaction fee to 1.65%.',
            'start_ts'    => 1597390475,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'filters'     => [
                'experiments'         => ['project_nitro', 'project_nitro_1'],
            ],
        ],
        [
            'title'       => 'Introducing Payment Buttons',
            'description' => 'Start accepting payments on your website or blog in less than 5 minutes. No coding needed.',
            'start_ts'    => 1598941800,
            'end_ts'      => 1617167373,
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
            'filters'     => [
                'activated' => 1,
            ]
        ],
        [
            'title'       => 'Free Credit Score!',
            'description' => 'Click Here to get your credit score along with the credit report for FREE!',
            'start_ts'    => 1604320769,
            'end_ts'      => 1617167373,
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
            'title'       => 'You\'re all set to accept payments',
            'description' => 'You\'ve successfully unlocked free payments for upto ₹2,00,000! To avail this offer, complete your first transaction before 16th of November',
            'start_ts'    => 1604320769,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/unlock.svg',
            'track_event' => true,
            'id'          => 'NOV20-RZP-FESTIVEOFFER',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept Payments',
                    'url'   => '',
                    'id'    => 'NOV20-RZP-FESTIVEOFFER-BUTTON'
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/links/festive-campaign-terms-conditions',
                ]
            ],
            'filters'     => [
                'campaigns' => ['UNLOCKFEST'],
            ]
        ],
        [
            'title'       => 'Festive Special: Exclusive Offer For You',
            'description' => 'Get ₹2,00,000 of free credits & 3 months of Opfin’s Payroll software for free.',
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV1',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV1-cta1',
                ],
            ],
            'start_ts'    => 1604925051,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement' => 'cohort-1'],
            ],
        ],
        // [
        //     'title'       => 'Boost International Sales With PayPal',
        //     'description' => 'Get upto 20% higher international success rates as well at T+1 settlement with PayPal',
        //     'start_ts'    => 1605160337,
        //     'end_ts'      => 1607752337,
        //     'icon'        => 'https://cdn.razorpay.com/static/assets/International_globe.png',
        //     'track_event' => true,
        //     'id'          => 'DEC20-PayPal-GTM',
        //     'buttons'     => [
        //         [
        //             'type'  => 'button',
        //             'label' => 'Enable Now',
        //             'url'   => '/config',
        //         ],
        //         [
        //             'type'  => 'primary-inverted',
        //             'label' => 'Know More',
        //             'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/paypal/',
        //         ],
        //     ],
        //     'filters'     => [
        //         'features'  => ['paypal_gtm_notification'],
        //     ],
        // ],
        [
            'title'       => 'Get ₹10,000 PayPal FREE Credits',
            'description' => 'Get ₹10K International Free Credits on PayPal. Enjoy 20% higher conversion, T+1 settlement and activation within 24hrs. TnCs apply.',
            'start_ts'    => 1607409081,
            'end_ts'      => 1609417824,
            'icon'        => 'https://cdn.razorpay.com/static/assets/International_globe.png',
            'track_event' => true,
            'id'          => 'DEC20-PayPal-GTM',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => '/config',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://lp.razorpay.com/links/international-free-credits-paypal',
                ],
            ],
            'filters'     => [
                'features'  => ['paypal_gtm_notification'],
            ],
        ],
        [
            'title'       => 'Easily Update Your Bank Account',
            'description' => 'In case you want to update your bank account, you can do so directly from your Razorpay account settings',
            'icon'        => '/dist/css/assets/ic_building.svg',
            'track_event' => true,
            'id'          => 'NOV20-PG-BANKUPDATE',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/profile#request-bank-account-change',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/profile/#change-bank-account-details',
                ],
            ],
            'start_ts'    => 1604925051,
            'end_ts'      => 1617193851,
            'filters'     => [
                'activation_status' => ['activated'],
                'role' => ['owner', 'admin'],
            ],
        ],
        [
            'title'       => 'Festive Special: Exclusive Offer For You',
            'description' => 'Get ₹2,00,000 of free credits & 3 months of Opfin’s Payroll software for free.',
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV2',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV2-cta1',
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement' => 'cohort-2'],
            ],
        ],
        [
            'title'       => 'Festive Special: Exclusive Offer For You',
            'description' => 'Get ₹2,00,000 of free credits & 3 months of Opfin’s Payroll software for free.',
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV2',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV2-cta1',
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement' => 'cohort-3'],
            ],
        ],
        [
            'title'       => 'Festive Special: Exclusive Offer For You',
            'description' => 'Get ₹5,00,000 of free credits & 3 months of Opfin’s Payroll software for free.',
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV3',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV3-cta1',
                ],
            ],
            'start_ts'    => 1609308877,
            'end_ts'      => 1623994200,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement_v2' => 'cohort-4'],
            ],
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
