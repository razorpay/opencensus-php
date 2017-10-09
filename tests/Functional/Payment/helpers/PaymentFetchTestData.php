<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFetchRuleCascadingForAdminAuth' => [
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

    'testFetchRulesForPrivateWithExtraFieldsError' => [
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

    'testFetchRulesCascadingForProxyAuth' => [
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

    'testFetchRulesWithSignedIdForPrivateAuth' => [
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

    'testFetchWithExpandsForProxyAuth' => [
        'request' => [
            'url' => '/payments',
            'method' => 'get',
            'content' => [
                'expand'  => ['card'],
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

    'testFindWithExpandsForPrivateAuth' => [
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

    'testFetchWithExpandsForPrivateAuthWithInvalidExpand' => [
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
    ],
];
