<?php

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
];
