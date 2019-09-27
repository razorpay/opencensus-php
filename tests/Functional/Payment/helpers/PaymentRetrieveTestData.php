<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testRetrievePaymentWithCardDetails' => [
        'request' => [
            // Updated dynamically via test method
            // 'url'     => '/payments/id',
            'method'  => 'get',
            'content' => [
                'expand' => ['card']
            ],
        ],
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'card' => [
                    'entity'        => 'card',
                    'last4'         => '1111',
                    'network'       => 'Visa',
                    'type'          => 'debit',
                    'issuer'        => 'hdfc',
                    'international' => false,
                    'emi'           => false,
                ],
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
                'acquirer_data'     => [],
                'tax'               => null,
            ],
        ],
    ],

    'testRetrieveMultiplePaymentsWithCardDetails' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'expand' => ['card']
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 3,
                'items' => [
                    [
                        'entity'            => 'payment',
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'international'     => null,
                        'method'            => 'netbanking',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => null,
                        'card_id'           => null,
                        'card'              => null,
                        'bank'              => 'HDFC',
                        'wallet'            => null,
                        'vpa'               => null,
                        'notes'             => [],
                        'fee'               => 23000,
                        'error_code'        => null,
                        'error_description' => null,
                        'acquirer_data'     => [],
                        'tax'               => null,
                    ],
                    [
                        'entity'            => 'payment',
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'international'     => false,
                        'method'            => 'card',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => null,
                        'card' => [
                            'entity'        => 'card',
                            'last4'         => '1111',
                            'network'       => 'Visa',
                            'type'          => 'debit',
                            'issuer'        => 'hdfc',
                            'international' => false,
                            'emi'           => false,
                        ],
                        'bank'              => null,
                        'wallet'            => null,
                        'vpa'               => null,
                        'notes'             => [],
                        'fee'               => 23000,
                        'error_code'        => null,
                        'error_description' => null,
                        'acquirer_data'     => [],
                        'tax'               => null,
                    ],
                    [
                        'entity'            => 'payment',
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'international'     => false,
                        'method'            => 'card',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => null,
                        'card' => [
                            'entity'        => 'card',
                            'last4'         => '1111',
                            'network'       => 'Visa',
                            'type'          => 'debit',
                            'issuer'        => 'hdfc',
                            'international' => false,
                            'emi'           => false,
                        ],
                        'bank'              => null,
                        'wallet'            => null,
                        'vpa'               => null,
                        'notes'             => [],
                        'fee'               => 23000,
                        'error_code'        => null,
                        'error_description' => null,
                        'acquirer_data'     => [],
                        'tax'               => null,
                    ],
                ],
            ],
        ],
    ],

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

    'testSearchEsForNotesOnAdminAuthRestricted' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => ['notes' => 'es'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'notes is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],

    'testSearchEsForNotesOnAdminAuthExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 1000,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'es',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
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
        'index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'es_random_1',
                                ],
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
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],
];
