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
                'tags' => ['announcement_early_settlements'],
                'not_tags' => ['es_automatic'],
                'activated' => 1,
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
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}

