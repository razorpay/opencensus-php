<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFetchRulesForAdminAuth' => [
        'request' => [
            'url' => '/admin/payment',
            'method' => 'get',
            'content' => [
                'amount' => 1000000
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testErrorFetchRulesForPrivateAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'email' => 'test@example.com'
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
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesForProxyAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'email' => 'test@example.com'
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testErrorFetchRulesForProxyAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'amount' => 1000000
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
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testEsRepositoryForProxyAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'notes' => 1000000
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testFetchWithSignedIdForPrivateAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'order_id' => ''
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testErrorFetchWithMaxCountForPrivateAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'count' => 1000
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

    'testFetchWithExpandsTrueForPrivateAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'expand' => ['card']
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'card' => [
                            'name' => 'Test Name'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFindWithExpandForPrivateAuth' => [
        'request' => [
            'url' => '/payments/',
            'method' => 'get',
            'content' => [
                'expand' => ['card']
            ],
        ],
        'response' => [
            'content' => [
                'card' => [
                    'name' => 'Test Name'
                ]
            ],
        ],
    ],

    'testErrorFindWithExpandForPrivateAuth' => [
        'request' => [
            'url' => '/payments/',
            'method' => 'get',
            'content' => [
                'expand' => ['card','fake']
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
    ]
];
