<?php

namespace App\Merchant\Notifications;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'Razorpay FTX',
            'description' =>
                'Join us for the largest Indian FinTech Conference happening in Bengaluru on the 7th of December',
            'start_ts'    => 1543465800,
            'end_ts'      => 1544166000,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/ftx.png',
            'buttons'     => [
                [
                    'type'  => 'link',
                    'label' => 'Speakers & Agenda',
                    'url'   => 'https://razorpay.typeform.com/to/SWOXx5',
                ]
            ],
            'filters'     => [
                'tags' => ['announcement_ftx_passes'],
            ]
        ],
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
                'tags' => ['announcement_early_settlements'],
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
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}

