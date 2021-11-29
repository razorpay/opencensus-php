<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateInvoiceWithUserId' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
    ],

    'testCreateInvoiceOrgAndMerchantFeature' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
    ],

    'testFailCreateInvoiceMerchantFeature' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'description' => 'You do not have permission to access this feature.',
                ],
            ],
            'status_code' => 400,
       ],
    ],

    'testFailCreateInvoiceOrgFeature' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
    ],


    'testGetInvoiceWithUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id' => 'inv_1000000invoice',
            ],
        ],
    ],

    'testGetInvoiceWithUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testGetInvoiceWithUserIdAndDifferentRoleHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'newrandomrole',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id' => 'inv_1000000invoice',
            ],
        ],
    ],

    'testListInvoiceWithUserIdHeader' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id' => 'inv_1000001invoice',
                    ],
                    [
                        'id' => 'inv_1000000invoice',
                    ]
                ]
            ],
        ],
    ],

    'testListInvoiceWithUserIdHeaderAndEsParams' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testListInvoiceWithoutUserIdHeader' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
            'server' => [],
        ],
        'response' => [
            'content' => [
                'count' => 3,
                'items' => [
                    [
                        'id' => 'inv_1000002invoice',
                    ],
                    [
                        'id' => 'inv_1000001invoice',
                    ],
                    [
                        'id' => 'inv_1000000invoice',
                    ]
                ]
            ],
        ],
    ],

    'testListInvoiceWithUserIdAndDifferentRoleHeader' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'newrandomrole',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 3,
                'items' => [
                    [
                        'id' => 'inv_1000002invoice',
                    ],
                    [
                        'id' => 'inv_1000001invoice',
                    ],
                    [
                        'id' => 'inv_1000000invoice',
                    ]
                ]
            ],
        ],
    ],

    'testUpdateInvoiceWithUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [
                'description' => 'Updated Description It Is',
            ],
        ],
        'response' => [
            'content' => [
                'id'          => 'inv_1000000invoice',
                'description' => 'Updated Description It Is',
            ],
        ],
    ],

    'testUpdateInvoiceWithUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [
                'description' => 'Updated Description It Is',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testUpdateInvoiceWithAgentUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '100AgentUserId',
                'HTTP_X-Dashboard-User-Role' => 'agent',
            ],
            'content' => [
                'description' => 'Updated Description It Is',
            ],
        ],
        'response' => [
            'content' => [
                'id'          => 'inv_1000000invoice',
                'description' => 'Updated Description It Is',
            ],
        ],
    ],

    'testUpdateInvoiceWithAgentUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '101AgentUserId',
                'HTTP_X-Dashboard-User-Role' => 'agent',
            ],
            'content' => [
                'description' => 'Updated Description It Is',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation can only be performed by the creator',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteInvoiceWithUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'delete',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDeleteInvoiceWithUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'delete',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testDeleteInvoiceWithAgentUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'delete',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '100AgentUserId',
                'HTTP_X-Dashboard-User-Role' => 'agent',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDeleteInvoiceWithAgentUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation can only be performed by the creator',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelInvoiceWithUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'     => 'inv_1000000invoice',
                'status' => 'cancelled',
            ],
        ],
    ],

    'testCancelInvoiceWithUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testCancelInvoiceWithAgentUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '100AgentUserId',
                'HTTP_X-Dashboard-User-Role' => 'agent',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'     => 'inv_1000000invoice',
                'status' => 'cancelled',
            ],
        ],
    ],

    'testCancelInvoiceWithAgentUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '100AgentUserId',
                'HTTP_X-Dashboard-User-Role' => 'agent',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation can only be performed by the creator',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    // ----------------------------------------------------------------------
    // Expectations for ES

    'testListInvoiceWithUserIdHeaderEsExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'user_id' => [
                                    'query' =>'10000000UserId',
                                    'boost' => 2,
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
        ],
    ],

    'testListInvoiceWithUserIdHeaderEsExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000000invoice'
                ],
                [
                    '_id' => '1000001invoice',
                ]
            ],
        ],
    ],

    // ----------------------------------------------------------------------
];
