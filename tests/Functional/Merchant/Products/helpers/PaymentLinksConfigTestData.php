<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testCreateDefaultPaymentLinksConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_links'
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#FFFFFF',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ]
                ],
                'product_name'         => 'payment_links'
            ],
        ]
    ],

    'testFetchPaymentLinksConfig' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{productId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#FFFFFF',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ]
                ],
                'product_name'         => 'payment_links'
            ],
        ]
    ],

    'testUpdatePaymentLinksConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'PATCH',
            'content' => [
                'checkout'    => [
                    'theme_color' => '#FFFFFA',
                ],
                'settlements' => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#FFFFFA',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '051610100039258',
                        'ifsc_code'      => 'UBIN0805165'
                    ]
                ],
                'product_name'         => 'payment_links'
            ],
        ]
    ],

];

