<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePaymentOriginMerchantKey' => [
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randomPaymentId',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ]
    ],

    'testCreatePaymentOriginPartnerKey' => [
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randomPaymentId',
                'origin_type' => 'application',
            ],
        ]
    ],

    'testCreatePaymentOriginOauthPublicToken' => [
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randomPaymentId',
                'origin_type' => 'application',
            ],
        ]
    ],

    'testCreatePaymentOriginPrivateAuth' => [
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randomPaymentId',
                'origin_type' => 'merchant',
            ],
        ]
    ],

    'testCreateOriginByInternalApp' => [
        'request'  => [
            'url'     => '/entity_origins',
            'method'  => 'POST',
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => '',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ],
    ],

    'testCreateApplicationOriginByInternalApp' => [
        'request'  => [
            'url'     => '/entity_origins',
            'method'  => 'POST',
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'application',
                'origin_id'   => 'randPaymentId1',
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'application',
                'origin_id'   => 'randPaymentId1',
            ],
        ],
    ],

    'testCreateApplicationOriginByInternalAppForPaymentLink' => [
        'request'  => [
            'url'     => '/entity_origins',
            'method'  => 'POST',
            'content' => [
                'entity_type' => 'payment_link',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'application',
                'origin_id'   => 'randPaymentId1',
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'payment_link',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'application',
                'origin_id'   => 'randPaymentId1',
            ],
        ],
    ],

    'testCreateMerchantOriginByInternalAppForPaymentLink' => [
        'request'  => [
            'url'     => '/entity_origins',
            'method'  => 'POST',
            'content' => [
                'entity_type' => 'payment_link',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'payment_link',
                'entity_id'   => 'randPaymentId1',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ],
    ],

    'testCreateOriginInvalidIdByInternalApp' => [
        'request'  => [
            'url'     => '/entity_origins',
            'method'  => 'POST',
            'content' => [
                'entity_type' => 'payment',
                'entity_id'   => 'pay_randPaymentId1',
                'origin_type' => 'merchant',
                'origin_id'   => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ]
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\DbQueryException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_DB_QUERY_FAILED,
        ],
    ],
];
