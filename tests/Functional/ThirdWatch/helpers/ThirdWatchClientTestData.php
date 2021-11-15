<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testTWAddressServiceabilityWithInvalidInput' => [
        'request' => [
            'url' => '/tw/address/check_cod_eligibility',
            'method' => 'post',
            'content' => [
                'address' => []
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testTWAddressServiceabilityWithValidAddress' => [
        'request' => [
            'url' => '/tw/address/check_cod_eligibility',
            'method' => 'post',
            'content' => [
                'address' => [
                    "type" => "shipping_address",
                    "line1" => "line1line1line1line1",
                    "line2" => "line2line2line2line2",
                    "zipcode" => "100000",
                    "city" => "Bengaluru",
                    "state" => "Karnataka",
                    "country" => "IN",
                    "id" => "id_should_pass",
                ],
                'order_id' => 'order_DfsabdSg',
            ],
        ],
        'response' => [
            'content' => [
                'cod' => true,
            ],
            'status_code' => 200,
        ],
    ],
    'testTWAddressServiceabilityWithInvalidAddress' => [
        'request' => [
            'url' => '/tw/address/check_cod_eligibility',
            'method' => 'post',
            'content' => [
                'address' => [
                    "type" => "shipping_address",
                    "line1" => "line1line1line1line1",
                    "line2" => "line2line2line2line2",
                    "zipcode" => "100000",
                    "city" => "Bengaluru",
                    "state" => "Karnataka",
                    "country" => "IN",
                    "id" => "id_should_fail",
                ],
                'order_id' => 'order_DfsabdSg',
            ],
        ],
        'response' => [
            'content' => [
                'cod' => false,
            ],
            'status_code' => 200,
        ],
    ],
    'testTWAddressServiceabilityWithThirdWatchCallTimeout' => [
        'request' => [
            'url' => '/tw/address/check_cod_eligibility',
            'method' => 'post',
            'content' => [
                'address' => [
                    "type" => "shipping_address",
                    "line1" => "line1line1line1line1",
                    "line2" => "line2line2line2line2",
                    "zipcode" => "100000",
                    "city" => "Bengaluru",
                    "state" => "Karnataka",
                    "country" => "IN",
                    "id" => "id_should_timeout",
                ],
                'order_id' => 'order_abc123',
            ],
        ],
        'response' => [
            'content' => [
                'cod' => false,
            ],
            'status_code' => 200,
        ],
    ],
    'testTWAddressServiceabilityCallbackInvalidInput' => [
        'request' => [
            'url' => '/tw/address/cod_score',
            'method' => 'post',
            'content' => [
                'score' => '0.123',
                'id' => '123'
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
    ],
    'testTWAddressServiceabilityCallback' => [
        'request' => [
            'url' => '/tw/address/cod_score',
            'method' => 'post',
            'content' => [
                'score' => 0.78,
                'label' => 'green',
                'id'    => 'id_should_write'
            ]
        ],
        'response' => [
            'content' => ['success' => true],
            'status_code' => 200
        ],
    ]
];
