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
                        'type'                  => 'mutual_fund',
                        'receipt'               => 'dummy_receipt1',
                        'plan'                  => 'dummy_plan1',
                        'scheme'                => 'dummy_scheme1',
                        'option'                => 'dummy_option1',
                        'amount'                => '12345',
                        'folio'                 => 'dummy_folio1',
                        'mf_member_id'          => 'dummy_mf_member_id',
                        'mf_user_id'            => 'dummy_mf_user_id',
                        'mf_partner'            => 'dummy_mf_partner',
                        'mf_investment_type'    => 'dummy_mf_investment_type',
                        'mf_amc_code'           => 'dummy_mf_amc_code',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type'          => 'loan',
                        'loan_number'   => '1234556',
                        'amount'        => '6789',
                        'receipt'       => 'dummy_receipt2',
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
                        'mf_member_id'          => 'dummy_mf_member_id',
                        'mf_user_id'            => 'dummy_mf_user_id',
                        'mf_partner'            => 'dummy_mf_partner',
                        'mf_investment_type'    => 'dummy_mf_investment_type',
                        'mf_amc_code'           => 'dummy_mf_amc_code',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type'          => 'loan',
                        'loan_number'   => '1234556',
                        'amount'        => '6789',
                        'receipt'       => 'dummy_receipt2',
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
                        'invalid_mutual_fund_key'        => 'random value',
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

    'testCreateOrderProductsAssocArrayFail' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products'      => [
                    'type'  => 'mutual_fund',
                    'ihno'  => '123',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => "'products' must be a list of 'product' objects and not an object",
                    'field'         => 'products'
                ],
            ],
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
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

    'testAmountMismatchError' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'                  => 'mutual_fund',
                        'receipt'               => 'dummy_receipt1',
                        'plan'                  => 'dummy_plan1',
                        'scheme'                => 'dummy_scheme1',
                        'option'                => 'dummy_option1',
                        'amount'                => '25000',
                        'folio'                 => 'dummy_folio1',
                        'mf_member_id'          => 'dummy_mf_member_id',
                        'mf_user_id'            => 'dummy_mf_user_id',
                        'mf_partner'            => 'dummy_mf_partner',
                        'mf_investment_type'    => 'dummy_mf_investment_type',
                        'mf_amc_code'           => 'dummy_mf_amc_code',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                    [
                        'type'          => 'loan',
                        'loan_number'   => '1234556',
                        'amount'        => '24000',
                        'receipt'       => 'dummy_receipt2',
                    ],
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => "Order amount not equal to sum of amount of all products",
                    'field'         => 'products'
                ],
            ],
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_ORDER_AND_PRODUCTS_AMOUNT_MISMATCH'
        ],
    ],

    'testAmountMismatchNoError' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'products' => [
                    [
                        'type'                  => 'mutual_fund',
                        'receipt'               => 'dummy_receipt1',
                        'plan'                  => 'dummy_plan1',
                        'scheme'                => 'dummy_scheme1',
                        'option'                => 'dummy_option1',
                        'amount'                => '12345',
                        'folio'                 => 'dummy_folio1',
                        'mf_member_id'          => 'dummy_mf_member_id',
                        'mf_user_id'            => 'dummy_mf_user_id',
                        'mf_partner'            => 'dummy_mf_partner',
                        'mf_investment_type'    => 'dummy_mf_investment_type',
                        'mf_amc_code'           => 'dummy_mf_amc_code',
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
                        'mf_member_id'          => 'dummy_mf_member_id',
                        'mf_user_id'            => 'dummy_mf_user_id',
                        'mf_partner'            => 'dummy_mf_partner',
                        'mf_investment_type'    => 'dummy_mf_investment_type',
                        'mf_amc_code'           => 'dummy_mf_amc_code',
                        'notes'         => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ]
                    ],
                ],
            ],
        ],
    ],
];
