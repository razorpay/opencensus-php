<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '110085',
                        'city'    => 'Delhi',
                        'state'   => 'Delhi',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '110085',
                        'city'    => 'Delhi',
                        'state'   => 'Delhi',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '305001',
                        'city'    => 'Ajmer',
                        'state'   => 'Rajasthan',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '110085',
                        'city'    => 'Delhi',
                        'state'   => 'Delhi',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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
    'testCustomerAdditionalInfoOrderCreate' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc'
                ],
            ],
        ],
    ],
    'testCustomerAdditionalInfoEmptyOrderCreate' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [],
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
    'testCustomerAdditionalInfoEmptyValuesOrderCreate' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '',
                    'property_value' => '',
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_MISSING_KEY_FIELD,
        ],
    ],
    'testCustomerAdditionalInfoFeatureNotPresentOrderCreate' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc',
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],
    'testCustomerAdditionalInfoMissingIdOrderCreate' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_value' => 'abc'
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
        ],
          'exception' => [
              'class' => RZP\Exception\BadRequestException::class,
              'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_MISSING_KEY_FIELD,
          ],
    ],
    'testUpdateCustomerDetailsForPlaced1CCOrder'     => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '110085',
                        'city'    => 'Delhi',
                        'state'   => 'Delhi',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
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

    'testReset1CCOrderWithPlacedOrder'              => [
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

    'testUpdateNocodeAppsCustomerDetailsFor1CCOrder'           => [
        'request'  => [
            'convertContentToString' => false,
            'method'                 => 'PATCH',
            'content'                => [
                'customer_details' => [
                    'contact'          => '+9191111111111',
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'line1'   => 'line123',
                        'zipcode' => '110085',
                        'city'    => 'Delhi',
                        'state'   => 'Delhi',
                        'country' => 'in',
                    ],
                    'device'    => [
                        'id' => '1.f66e640403baead0c2718eb27aebe580de325842.1643739530086.12345678',
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ],
    ],
];
