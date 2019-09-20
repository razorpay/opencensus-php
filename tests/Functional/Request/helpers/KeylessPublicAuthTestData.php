<?php

namespace RZP\Tests\Functional\Request;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetInvoiceStatus' => [
        'request' => [
            'method' => 'get',
            'url'    => '/invoices/inv_1000000invoice/status',
        ],
        'response' => [
            'content' => [
                'status' => 'issued',
            ],
        ],
    ],

    'testGetInvoiceStatusOfInvalidId1' => [
        'request' => [
            'method' => 'get',
            'url'    => '/invoices/in_1000000invoice/status',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetInvoiceStatusOfInvalidId2' => [
        'request' => [
            'method' => 'get',
            'url'    => '/invoices/inv_1000001invoice/status',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetCheckoutPreferencesWithNoXEntityId' => [
        'request' => [
            'method' => 'get',
            'url'    => '/preferences',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Please provide your api key for authentication purposes.',
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testGetCheckoutPreferencesWithXEntityIdInQuery' => [
        'request' => [
            'method' => 'get',
            'url'    => '/preferences?x_entity_id=order_100000000order',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            // Just asserting that the response is 200
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithOrderIdInQuery' => [
        'request' => [
            'method' => 'get',
            'url'    => '/preferences?order_id=order_100000000order',
        ],
        'response' => [
            // Asserts that besides, normal response order object is also coming in the 200 response
            'content' => [
                'mode'  => 'live',
                'order' => [
                    'partial_payment' => false,
                    'amount'          => 1000000,
                    'currency'        => 'INR',
                    'amount_paid'     => 0,
                    'amount_due'      => 1000000,
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithInvoiceIdInQuery' => [
        'request' => [
            'method' => 'get',
            'url'    => '/preferences?invoice_id=inv_1000000invoice',
        ],
        'response' => [
            // Asserts that besides, normal response order & invoice object are also coming in the 200 response
            'content' => [
                'invoice' => [
                    'order_id' => 'order_100000invorder',
                    'url'      =>  'http://bitly.dev/2eZ11Vn',
                    'amount'   =>  1000000,
                ],
                'order' => [
                    'partial_payment' => false,
                    'amount'          => 1000000,
                    'amount_paid'     => 0,
                    'amount_due'      => 1000000,
                ],
            ],
        ],
    ],
];
