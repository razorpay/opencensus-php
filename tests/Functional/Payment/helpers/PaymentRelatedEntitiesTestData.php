<?php

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testFetchOrdersEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'order' => [
                    'entity'   => 'order',
                ],
            ],
        ],
    ],

    'testFetchOrdersEntityInvalidId' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
        'response' => [
            'content' => [
                'error'   => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchMerchantsEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'entity'   => 'merchant',
                ],
            ],
        ],
    ],

    'testFetchFeaturesEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'feature' => [
                    'entity'   => 'feature',
                ],
            ],
        ],
    ],

    'testFetchTokenEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'token' => [
                    'entity'   => 'token',
                ],
            ],
        ],
    ],

    'testFetchIinsEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'iin' => [
                    'iin'       => '411111',
                    "category"  => "CLASSIC",
                    "network"   => "Visa",
                ],
            ],
        ],
    ],

    'testCreateCardEntityWithToken' => [
        'request' => [
            'url' => '/cardps/entity/create/card',
            'method' => 'POST',
            'content' => [
                'card' => [
                    'name' => 'shk',
                    'expiry_month' => 04,
                    'expiry_year'  => 2025,
                    'vault_token'  => '4111466126747568',
                    'iin'          => '411146',
                    'vault'        => 'rzpvault'
                ],
                'save_token' => true,
                'customer' => [
                    'id' => 'customer123'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'card' => [
                ],
                'token' => [
                    'entity' => 'token'
                ]
            ],
        ],
    ],

    'testCreateCardEntityWithoutToken' => [
        'request' => [
            'url' => '/cardps/entity/create/card',
            'method' => 'POST',
            'content' => [
                'card' => [
                    'name' => 'shk',
                    'expiry_month' => 04,
                    'expiry_year'  => 2025,
                    'iin'          => '411146',
                    'vault_token'  => '4111466126747568',
                    'vault'        => 'rzpvault'
                ],
            ]
        ],
        'response' => [
            'content' => [
                'card' => [
                ]
            ],
        ],
    ],

    'testPaymentMetaSearch' => [
        'request' => [
            'url' => '/payments/meta/reference',
            'method' => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ]
        ],
    ]
];
