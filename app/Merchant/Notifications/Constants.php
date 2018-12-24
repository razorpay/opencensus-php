<?php

namespace App\Merchant\Notifications;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'Early Settlements',
            'description' =>
                'Get your payments settled within a few hours and never have a shortfall of working capital. ',
            'start_ts'    => 1542688200,
            'end_ts'      => 1545280200,
            'icon'        => 'settlements',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Request Access',
                    'url'   => '/settlements#requestearlyaccess',
                ],
            ],
            'filters'     => [
                'tags'         => ['announcement_early_settlements'],
                'not_tags'     => ['es_automatic'],
                'activated'    => 1,
                'not_features' => ['es_on_demand']
            ]
        ],
        [
            'title'       => 'Prices Slashed',
            'description' =>
                'Start transacting with us and enjoy our 1.75% slashed pricing, valid only until 31st of January, 2019',
            'start_ts'    => 1541565000,
            'end_ts'      => 1548959399,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/offer.png',
            'buttons'     => [
                [
                    'type'  => 'link',
                    'label' => 'View T&Cs',
                    'url'   => 'https://razorpay.com/pricing/',
                ],
            ],
            'filters'     => []
        ],
        [
            'title'       => 'All New Payment Pages!',
            'description' => 'Payment pages now has a ton of enhanced features, a lot more customisation and a better look and feel. Check it out now!',
            'start_ts'    => 1545213008,
            'end_ts'      => 1545762599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/paymentpages.png',
            'buttons'     => [
                [
                    'type'  => 'link',
                    'label' => 'Go To Payment Pages',
                    'url'   => 'https://dashboard.razorpay.com/#/app/paymentpages',
                ],
            ],
            'filters'     => [
                'tags' => ['pp_notify_existing'],
            ]
        ],
        [
            'title'       => 'Payment Pages - Now Live!',
            'description' => 'The wait is over, we finally have the product live. Thanks for showing interest. Start creating your custom branded page now! ',
            'start_ts'    => 1545213008,
            'end_ts'      => 1545762599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/paymentpages.png',
            'buttons'     => [
                [
                    'type'  => 'link',
                    'label' => 'Go To Payment Pages',
                    'url'   => 'https://dashboard.razorpay.com/#/app/paymentpages',
                ],
            ],
            'filters'     => [
                'tags' => ['pp_notify_earlyaccess'],
            ]
        ],
        [
            'title'       => 'RazorpayX',
            'description' => 'With RazorpayX track, automate and accelerate every aspect of your financial payouts',
            'start_ts'    => 1545625800,
            'end_ts'      => 1554006600,
            // TODO: change the icon to razorx
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/paymentpages.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Go To RazorpayX',
                    'url'   => 'https://x.razorpay.com',
                ],
            ],
            'filters'     => [
                'tags' => ['announcement_razorpayx'],
            ]
      ],        
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}

