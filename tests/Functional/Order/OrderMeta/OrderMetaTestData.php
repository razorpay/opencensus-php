<?php

use RZP\Error\ErrorCode;

return [
    'test1CCOrderCreate'                             => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'line_items_total' => 1000,
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'line_items_total' => 1000,
            ],
        ],
    ],
    'testNon1CCOrderCreateFor1CCMerchant' => [
        'request' => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
            ],
        ],
    ],
    'testUpdateCustomerDetailsFor1CCOrder'           => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ],
    ],
    'testUpdateCustomerDetailsFor1CCOrderForNonServiceableAddress'           => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND,
        ]
    ],
    'testUpdateCustomerDetailsFor1CCOrderWithoutServiceabilityDetailsInCache'           => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND,
        ]
    ],


    'testUpdateCustomerDetailsForNon1CCOrder'        => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER,
        ],
    ],
    'testUpdateCustomerDetailsForNon1CCMerchant'     => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_NON_1CC_MERCHANT,
        ],
    ],
    'testUpdateCustomerDetailsForPaid1CCOrder'     => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+919954246991',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
        ],
    ],
    'testReset1CCOrder'                              => [
        'request'  => [
            'method' => 'post',
        ],
        'response' => [
            'status_code' => 200,
            'content'   => []
        ],
    ],
    'testReset1CCOrderWithPaidOrder'              => [
        'request'  => [
            'method' => 'POST',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
        ],
    ],
    'testReset1CCOrderWithNon1CCOrder'              => [
        'request'  => [
            'method' => 'POST',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER,
        ],
    ],
];
