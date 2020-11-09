<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'Pay vendor invoices in seconds.',
            'description' => 'Welcome to the easiest way to make vendor payments: End-to-end automation for adding, tracking, and clearing invoice & TDS payments.',
            'start_ts'    => 1604904751,
            'end_ts'      => 1612084863,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-button.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => 'https://x.razorpay.com/vendor-payments',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Schedule Demo',
                    'url'   => 'https://calendly.com/payouts/vendor-payouts-demo-razorpayx',
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
        ]
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
