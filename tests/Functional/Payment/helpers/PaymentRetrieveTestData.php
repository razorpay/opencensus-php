<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSearchEsForNotesPrivateAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => ['notes' => 'es_random_1'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],

    'testMoreThan100InPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The count may not be greater than 100.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testSearchEsForStatus' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => ['status' => 'authorized'],
        ],
        'response' => [
            'content' => ['count' => 1]
        ],
    ],

    'testSearchEsEntityNotPresentInMySql' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => ['notes' => 'es_random_1'],
        ],
        'response' => [
            'content' => ['count' => 0]
        ],
    ],

    'testSearchEsWithoutQueryParams' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => ['count' => 1]
        ],
    ],

    'testSearchEsForNotesOnAdminAuth' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => ['notes' => 'es'],
        ],
        'response' => [
            'content' => ['count' => 4]
        ]
    ],

    'testSearchEsForNotesOnAdminAuthExpectedSearchParams' => [
        'index' => 'payment_test',
        'type'  => 'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 1000,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query'  => 'es',
                                'type'   => 'best_fields',
                                'fields' => 'notes.*',
                                'boost'  => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testSearchEsForNotesWithMerchantIdInQueryParamsOnProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => ['notes' => 'es_random_1', 'merchant_id' => '12345678901234'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],

    'testSearchEsForNotes' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => ['notes' => 'es_random_1'],
        ],
        'response' => [
            'content' => ['count' => 1],
        ],
    ],

    'testSearchEsForNotesExpectedSearchParams' => [
        'index' => 'payment_test',
        'type'  => 'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query'  => 'es_random_1',
                                'type'   => 'best_fields',
                                'fields' => 'notes.*',
                                'boost'  => 2,
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
