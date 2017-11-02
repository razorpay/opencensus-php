<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFetchForPrivateAuth' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'GET',
            'content' => [
                'type' => 'invoice',
            ]
        ],
        'response' => [
            'content' => [
                'count'  => 1,
                'entity' => 'collection',
                'items'  => [
                    [
                        'id' => 'inv_1000000invoice',
                    ],
                ],
            ],
        ],
    ],

    'testFindForPrivateAuth' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'GET',
            'content' => [
                'type' => 'invoice',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'invoice',
                'id'     => 'inv_1000000invoice',
                'type'   => 'invoice',
            ],
        ],
    ],

    'testFetchRuleCascadingForAdminAuth' => [
        'request' => [
            'url'     => '/admin/invoice',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'count'  => 1,
                'entity' => 'collection',
                'items'  => [
                    [
                        'id' => 'inv_1000000invoice',
                    ],
                ],
            ],
        ],
    ],

    'testFetchRulesForProxyAuthWithExtraFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'GET',
            'content' => [
                'order_id' => 'xyz',
            ],
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
            'class'               => \RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesForProxyAuthWithInvalidField' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'GET',
            'content' => [
                'type' => 'xyz',
            ],
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
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchWithExpandsForProxyAuth' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'GET',
            'content' => [
                'expand' => [
                    'payments',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'   => 'invoice',
                        'id'       => 'inv_1000000invoice',
                        'payments' => [
                            'count' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFindWithExpandsForProxyAuth' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'payments',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'invoice',
                'id'       => 'inv_1000000invoice',
                'payments' => [
                    'count' => 1,
                ],
            ],
        ],
    ],

    'testFindForProxyAuthWithInvalidExpands' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'payments',
                    'fake',
                ],
            ],
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchForSoftDeletedInvoiceForAppAuth' => [
        'request' => [
            'url'     => '/admin/invoice',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'count'  => 1,
                'entity' => 'collection',
                'items'  => [
                    [
                        'id' => 'inv_1000000invoice',
                    ],
                ],
            ],
        ],
    ]
];
