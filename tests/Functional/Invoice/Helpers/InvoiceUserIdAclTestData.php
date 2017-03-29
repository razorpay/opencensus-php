<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateInvoiceWithUserIdHeader' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
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
                'user_id'       => '10000000UserId',
            ],
        ],
    ],

    'testCreateInvoiceWithUserIdAndDiffRoleHeader' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'newrandomrole',
            ],
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
                'user_id'       => '10000000UserId',
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
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
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

    'testExpireInvoiceWithUserIdHeaderSuccess' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/expire',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard-User-Id'   => '10000000UserId',
                'HTTP_X-Dashboard-User-Role' => 'sellerapp',
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'     => 'inv_1000000invoice',
                'status' => 'expired',
            ],
        ],
    ],

    'testExpireInvoiceWithUserIdHeaderForbidden' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice/expire',
            'method' => 'post',
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

    // ----------------------------------------------------------------------
    // Expectations for ES

    'testListInvoiceWithUserIdHeaderEsExpectedSearchParams' => [
        'index' => 'test_invoice',
        'type'  => 'test_invoice',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'user_id' => [
                                    'value' =>'10000000UserId',
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
