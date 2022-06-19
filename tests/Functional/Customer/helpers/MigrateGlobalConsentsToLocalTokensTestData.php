<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testBulkCreateLocalTokensFromConsent' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'merchantId' => "10000merchant1",
                        'tokenId' => "100022xytoken1",
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                    ],
                ],
            ],
        ],
    ],
    'testBulkCreateLocalTokensFromConsentWhenValidTokenAndMerchantExpectsLocalTokenCreation' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
                [
                    'tokenId' => '100022xytoken2',
                    'merchantId' => '10000merchant2',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS52',
                ],
                [
                    'tokenId' => '100022xytoken3',
                    'merchantId' => '10000merchant3',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS53',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'     => [
                    [
                        'merchantId' => "10000merchant1",
                        'tokenId' => "100022xytoken1",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                    ],
                    [
                        'merchantId' => "10000merchant2",
                        'tokenId' =>  "100022xytoken2",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS52",
                    ],
                    [
                        'merchantId' => "10000merchant3",
                        'tokenId' => "100022xytoken3",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS53",
                    ],
                ],
            ],
        ],
    ],
    'testBulkCreateLocalTokensFromConsentWhenInvalidTokenAndValidMerchantExpectsLocalTokenCreationFailure' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
             'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                        'merchantId' => "10000merchant1",
                        'tokenId' => "100022xytoken1",
                        'success' => false,
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                        'error' => [
                            'description' => 'Token is deleted or does not exist',
                        ],
                    ],
                ],
             ],
        ],
    ],
    'testBulkCreateLocalTokensFromConsentWhenDuplicateTokenExpectsLocalTokenCreationFailure' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS52',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'merchantId' => "10000merchant1",
                        'tokenId' => "100022xytoken1",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                    ],
                    [
                        'merchantId' => "10000merchant1",
                        'tokenId' =>  "100022xytoken1",
                        'success' => false,
                        'idempotency_key' => "batch_JRyOR7f18LUS52",
                    ],
                ],
            ],
        ],
    ],
    'testBulkCreateLocalTokensFromConsentWhenValidTokenAndInvalidMerchantExpectsLocalTokenCreationFailure' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000merchant1',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
             'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                        'merchantId' => "10000merchant1",
                        'tokenId' => "100022xytoken1",
                        'success' => false,
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                        'error' => [
                            'description' => 'Merchant is deleted or does not exist',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testBulkCreateLocalTokensFromConsentWhenOneInvalidTokenExpectsLocalTokenCreationFailureOnInvalidToken' => [
        'request'  => [
            'url'     => '/create_local_tokens_from_consents/bulk',
            'content' => [
                [
                    'tokenId' => '100022xytoken1',
                    'merchantId' => '10000000000000',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS51',
                ],
                [
                    'tokenId' => '100022xytoken2',
                    'merchantId' => '10000merchant2',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS52',
                ],
                [
                    'tokenId' => '100022xytoken3',
                    'merchantId' => '10000merchant3',
                    'idempotency_key' =>  'batch_JRyOR7f18LUS53',
                ],
            ],
            'server'    =>  [
                'HTTP_X_Batch_Id'    => 'JRyOR7f18LU711',
            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'     => [
                    [
                        'merchantId' => "10000000000000",
                        'tokenId' => "100022xytoken1",
                        'success' => false,
                        'idempotency_key' => "batch_JRyOR7f18LUS51",
                    ],
                    [
                        'merchantId' => "10000merchant2",
                        'tokenId' =>  "100022xytoken2",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS52",
                    ],
                    [
                        'merchantId' => "10000merchant3",
                        'tokenId' => "100022xytoken3",
                        'success' => true,
                        'idempotency_key' => "batch_JRyOR7f18LUS53",
                    ],
                ],
            ],
        ],
    ],
    'testAsyncTokenisationOfGlobalCustomerLocalToken' => [
        'request'  => [
            'url'      => '/tokenisation/global/local_cards',
            'method'   => 'post',
            'content'  => []
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],
];
