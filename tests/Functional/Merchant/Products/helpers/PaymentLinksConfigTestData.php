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

    'testUpdateSettlementsDuringAKPstate' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You can not update this value as it is already verified.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_REMAINING_KYC_FIELDS_ARE_ALLOWED,
        ],
    ],

];

