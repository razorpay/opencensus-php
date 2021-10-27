<?php

return [
    'testCreateCodSlabs'                => [
        'request'  => [
            'url'     => '/merchant/slabs/cod',
            'method'  => 'POST',
            'content' => [
                'slabs' => [
                    [
                        "amount" => 1000, "fee" => 100,
                    ],
                    [
                        "amount" => 500, "fee" => 50,
                    ],
                    [
                        "amount" => 0, "fee" => 0,
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 201,
            'content'     => [],
        ],
    ],
    'testCreateShippingSlabs'           => [
        'request'  => [
            'url'     => '/merchant/slabs/shipping',
            'method'  => 'POST',
            'content' => [
                'slabs' => [
                    [
                        "amount" => 1000, "fee" => 0,
                    ],
                    [
                        "amount" => 500, "fee" => 50,
                    ],
                    [
                        "amount" => 0, "fee" => 100,
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 201,
            'content'     => [],
        ],
    ],
    'testCreateCodSlabWithInvalidRange' => [
        'request'  => [
            'url'     => '/merchant/slabs/cod',
            'method'  => 'POST',
            'content' => [
                'slabs' => [
                    [
                        "amount" => 1000, "fee" => 1000,
                    ],
                    [
                        "amount" => 500, "fee" => 50,
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [],
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],
];
