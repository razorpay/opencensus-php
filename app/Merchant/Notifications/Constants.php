<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
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
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your transaction fee to 1.65%.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
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
        ]
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
