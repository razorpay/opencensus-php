<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFetchForPrivateAuth' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'GET',
            'content' => [
                'type' => 'invoice'
            ]
        ],
        'response' => [
            'content' => [
                'count' => 1
            ]
        ]
    ],

    'testFindForPrivateAuth' => [
        'request' => [
            'url' => '/invoices/',
            'method' => 'GET',
            'content' => [
                'type' => 'invoice'
            ]
        ],
        'response' => [
            'content' => [
                'type' => 'invoice'
            ]
        ]
    ],

    'testFetchRuleCascadingForAdminAuth' => [
        'request' => [
            'url' => '/admin/invoice',
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'count' => 1
            ]
        ]
    ],

    'testFetchRulesForProxyAuthWithExtraFields' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'GET',
            'content' => [
                'order_id' => 'xyz'
            ]
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
            'class' => \RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesForProxyAuthWithInvalidField' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'GET',
            'content' => [
                'type' => 'xyz'
            ]
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
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchWithExpandsForProxyAuth' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'GET',
            'content' => [
                'expand' => ['payments']
            ]
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'payments' => [
                            'count' => 1
                        ]
                    ]
                ]
            ]
        ]
    ],

    'testFindWithExpandsForProxyAuth' => [
        'request' => [
            'url' => '/invoices/',
            'method' => 'get',
            'content' => [
                'expand' => ['payments']
            ],
        ],
        'response' => [
            'content' => [
                'payments' => [
                    'count' => 1
                ]
            ],
        ],
    ],

    'testFindForProxyAuthWithInvalidExpands' => [
        'request' => [
            'url' => '/invoices/',
            'method' => 'get',
            'content' => [
                'expand' => ['payments', 'fake']
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
