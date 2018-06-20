<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFetchRuleCascadingForAdminAuth' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'amount' => 1000000,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testFetchRuleVPAFilterForAdminAuth' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'vpa' => 'success1@razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'vpa' => 'success1@razorpay',
                    ]
                ],
            ],
        ],
    ],

    'testFetchCardQueryParams' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'iin'   => '411111',
                'last4' => '1111',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'card_id' => 'card_100000001lcard',
                    ]
                ],
            ],
        ],
    ],

    'testFetchRulesForPrivateWithExtraFieldsError' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'email' => 'test@example.com',
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],


    'testFetchRuleswithCustomerIdError' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_9evnGgkvo0XnSh',
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesCascadingForProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'email' => 'test@example.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testErrorFetchRulesForProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'amount' => 1000000,
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesWithSignedIdForPrivateAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'order_id' => '',
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
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'expand'  => [
                    'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'payment',
                        'card' => [
                            'name' => 'Test Name'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFindWithExpandsForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'card'   => [
                    'name' => 'Test Name'
                ],
            ],
        ],
    ],

    'testFindWithExpandsForPrivateAuthWithInvalidExpand' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'card',
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

    'testFetchWithDisputes' => [
        'request'   => [
            'method'  => 'get',
            'url'     => '/payments',
            'content' => [
                'email'  => 'abc@email.com',
                'expand' => [
                    'disputes',
                ],
            ],
        ],
        'response'  => [
            'content'       => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'             => 'payment',
                        'amount'             => 1000000,
                        'currency'           => 'INR',
                        'status'             => 'captured',
                        'method'             => 'card',
                        'amount_refunded'    => 0,
                        'amount_transferred' => 0,
                        'captured'            => true,
                        'email'             => 'abc@email.com',
                        'fee'               => 0,
                        'disputes'          => [
                            'entity' => 'collection',
                            'count'  => 2,
                            'items'  => [
                                [
                                    'amount'      => 1000000,
                                    'currency'    => 'INR',
                                    'reason_code' => 'SOMETHING_BAD',
                                    'status'      => 'open',
                                    'phase'       => 'chargeback',
                                ],
                                [
                                    'amount'      => 1000000,
                                    'currency'    => 'INR',
                                    'reason_code' => 'SOMETHING_BAD',
                                    'status'      => 'open',
                                    'phase'       => 'chargeback',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
