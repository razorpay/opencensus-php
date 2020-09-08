<?php

use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateOrderWithProducts' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'          => 'mutual_fund',
                        'receipt'       => 'dummy_receipt1',
                        'plan'          => 'dummy_plan1',
                        'scheme'        => 'dummy_scheme1',
                        'option'        => 'dummy_option1',
                        'amount'        => '12345',
                        'folio'         => 'dummy_folio1',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type'          => 'mutual_fund',
                        'receipt'       => 'dummy_receipt2',
                        'plan'          => 'dummy_plan2',
                        'scheme'        => 'dummy_scheme2',
                        'option'        => 'dummy_option2',
                        'amount'        => '6789', // not sending folio+notes keys(as its optional)
                    ],
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'          => 'mutual_fund',
                        'receipt'       => 'dummy_receipt1',
                        'plan'          => 'dummy_plan1',
                        'scheme'        => 'dummy_scheme1',
                        'option'        => 'dummy_option1',
                        'amount'        => '12345',
                        'folio'         => 'dummy_folio1',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type'          => 'mutual_fund',
                        'receipt'       => 'dummy_receipt2',
                        'plan'          => 'dummy_plan2',
                        'scheme'        => 'dummy_scheme2',
                        'option'        => 'dummy_option2',
                        'amount'        => '6789',
                    ],
                ],
            ],
        ],
    ],

    'testCreateOrderInvalidProductType' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'          => 'stock', // stock as a type is not supported now
                        'receipt'       => 'dummy_receipt1',
                        'plan'          => 'dummy_plan1',
                        'scheme'        => 'dummy_scheme1',
                        'option'        => 'dummy_option1',
                        'amount'        => '12345',
                        'folio'         => 'dummy_folio1',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'stock is not a valid product type',
                    'field'         => 'type',
                ],
            ],
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE'
        ],
    ],

    'testCreateOrderMissingProductType' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'receipt'       => 'dummy_receipt1',
                        'plan'          => 'dummy_plan1',
                        'scheme'        => 'dummy_scheme1',
                        'option'        => 'dummy_option1',
                        'amount'        => '12345',
                        'folio'         => 'dummy_folio1',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'The type field is required for Product',
                ],
            ],
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE'
        ],
    ],


    'testCreateOrderMutualFundProductInvalidKey' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'                           => 'mutual_fund',
                        'receipt'                        => 'dummy_receipt1',
                        'plan'                           => 'dummy_plan1',
                        'scheme'                         => 'dummy_scheme1',
                        'option'                         => 'dummy_option1',
                        'amount'                         => '12345',
                        'invalid_mutual_fund_key'         => 'random value',
                    ],
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'invalid_mutual_fund_key is/are not required and should not be sent for product of type mutual_fund',
                    'field'         => [
                        'invalid_mutual_fund_key'
                    ],
                ],
            ],
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE'
        ],
    ],

    'testCreateOrderNoProducts' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],
];
