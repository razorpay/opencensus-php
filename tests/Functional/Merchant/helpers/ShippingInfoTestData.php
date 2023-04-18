<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetShippingInfo' => [
        'request' => [
            'url' => '/merchant/shipping_info',
            'method' => 'post',
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'country' => 'in'
                    ],
                ],
                'mock_response' => [
                    'body' => [
                        'addresses' => [
                            [
                                'id' => 0,
                                'zipcode' => '560102',
                                'state' => 'Delhi',
                                'state_code' => 'DL',
                                'city' => 'South West Delhi',
                                'country' => 'in',
                                'serviceable' => true,
                                'cod' => true,
                                'cod_fee' => 50,
                                'shipping_fee' => 90,
                            ],
                        ],
                    ],
                    'status_code' => 200,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'state' => 'Delhi',
                        'state_code' => 'DL',
                        'city' => 'South West Delhi',
                        'country' => 'in',
                        'serviceable' => true,
                        'cod' => true,
                        'cod_fee' => 50,
                        'shipping_fee' => 90,
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testGetShippingInfoWithFailedZipcodeSearch' => [
        'request' => [
            'url' => '/merchant/shipping_info',
            'method' => 'post',
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'country' => 'in'
                    ],
                ],
                'mock_response' => [
                    'body' => [
                        'addresses' => [
                            [
                                'id' => 0,
                                'zipcode' => '560102',
                                'state' => '',
                                'state_code' => '',
                                'city' => '',
                                'country' => 'in',
                                'serviceable' => true,
                                'cod' => true,
                                'cod_fee' => 50,
                                'shipping_fee' => 90,
                            ]
                        ],
                    ],
                    'status_code' => 200,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'state' => '',
                        'state_code' => '',
                        'city' => '',
                        'country' => 'in',
                        'serviceable' => true,
                        'cod' => true,
                        'cod_fee' => 50,
                        'shipping_fee' => 90,
                    ],
                ],
            ],
        ],
        'status_code' => 200
    ],

    'testGetShippingInfoWithoutValidOrderId' => [
        'request' => [
            'url' => '/merchant/shipping_info',
            'method' => 'post',
            'content' => [
                'order_id' => '123',
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'country' => 'in'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
    'testGetShippingInfoForInvalidMerchantResponse' => [
        'request' => [
            'url' => '/merchant/shipping_info',
            'method' => 'post',
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'country' => 'in'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 503,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION,
        ],
    ],

    'testGetShippingInfoForNocodeAppsFeatureNotEnabled' => [
        'request' => [
            'url' => '/merchant/shipping_info',
            'method' => 'post',
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'country' => 'in'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'addresses' => [
                    [
                        'zipcode' => '560102',
                        'state' => 'Delhi',
                        'state_code' => 'DL',
                        'city' => 'South West Delhi',
                        'country' => 'in',
                        'serviceable' => true,
                        'cod' => false,
                        'cod_fee' => 0,
                        'shipping_fee' => 0,
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

];
