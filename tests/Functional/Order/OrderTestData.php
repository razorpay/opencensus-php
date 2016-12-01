<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testCreateOrderWithNegativeAmount' => [
        'request' => [
            'content' => [
                'amount'          => -200,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
         'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateAutoCaptureOrder' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
        ],
    ],
    'testCreateTPVOrder' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '0040304030403040',
                'bank'           => 'ANDB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                // 'method'         => 'netbanking',
                // 'account_number' => '0040304030403040',
            ],
        ],
    ],
    'testGetOrder' => [
        'amount'        => 50000,
        'currency'      => 'INR',
        'receipt'       => 'rcptid42',
    ],

    'testGetMultipleOrders' => [
        'request' => [
            'url' => '/orders',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testStatusAfterPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID
        ],
    ],

    'testPaymentForTPVMerchantWithoutOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED
        ],
    ],

    'testPaymentWithIncorrectBankForTPVMerchantWithOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Order bank does not match the payment bank',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testOrderAndPaymentAmountMismatch' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH
        ],
    ],

    'testPreferencesForTPVMerchants' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'netbanking' => [
                        'ALLA'   =>  'Allahabad Bank',
                        'ANDB'   =>  'Andhra Bank',
                        'CIUB'   =>  'City Union Bank',
                        'CORP'   =>  'Corporation Bank',
                        'IBKL'   =>  'IDBI Bank',
                        'INDB'   =>  'IndusInd Bank',
                        'KVBL'   =>  'Karur Vysya Bank',
                        'LAVB_R' =>  'Lakshmi Vilas Bank - Retail Banking',
                    ],
                ],
                'order' => [
                    'bank'           => 'ANDB',
                    'account_number' => 'XXXXXXXXXXXXXX40',
                ],
            ],
        ],
    ],
];
