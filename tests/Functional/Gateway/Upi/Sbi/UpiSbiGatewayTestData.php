<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Upi\Base\Entity as Upi;

return [
    'testFailedCollect' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment was unsuccessful due to a temporary issue. If amount got deducted, it will be refunded within 5-7 working days.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testFailedVpaValidation' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA
        ],
    ],

    'testRejectedCollect' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED
        ],
    ],

    'testCbsDownCollectRequest' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment was unsuccessful as you could not complete it in time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT
        ],
    ],

    'testVerifyFailed' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ]
    ],

    'testAmountAssertionFailure' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => RZP\Exception\LogicException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED
        ]
    ],

    'testUpiResponseAssertionFailure' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => RZP\Exception\AssertionException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_ASSERTION_ERROR
        ]
    ],

    'testValidateVpaSuccess' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaSuccessWithBlockedDBSave' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaSuccessWithoutBlockedDBSave' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaSuccessWithRazorx' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaSuccessWithPrefixSpace' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => ' success@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaSuccessWithPrefixAndSuffixSpace' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => '   success@sbi ',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@sbi',
                'success'       => true,
                'customer_name' => 'Test User',
            ],
        ]
    ],

    'testValidateVpaFailure' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'failedvalidate@sbi',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'failedvalidate@sbi',
                'success'       => false,
                'customer_name' => null,
            ],
        ]
    ],

    'testValidateAccountVpaTimeout' => [
        'request' => [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'vpa',
                'value'  => 'timeout@sbi'
            ],
        ],
        'response'  => [
            'content'     => [
                'error'   => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ]
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
    ],

    'testValidateAccountVpa' => [
        'request' => [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'vpa',
                'value'  => 'success@sbi'
            ],
        ],
        'response'  => [
            'content'     => [
                'vpa'           => "success@sbi",
                'success'       => true,
                'customer_name' => "*********",
            ],
            'status_code' => 200,
        ],
    ],

    'testValidateAccountVpaFailed' => [
        'request' => [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'vpa',
                'value'  => 'failedvalidate@sbi'
            ],
        ],
        'response'  => [
            'content'     => [
                'vpa'           => "failedvalidate@sbi",
                'success'       => false,
                'customer_name' => null,
            ],
            'status_code' => 200,
        ],
    ],

    'testValidateAccountVpaGatewayError' => [
        'request' => [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'vpa',
                'value'  => 'exception@sbi'
            ],
        ],
        'response'  => [
            'content'     => [
                'error'   => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                    ]
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
    ],

    'testValidateAccountInvalidInput' => [
        'request' => [
            'url'    => '/payments/validate/account',
            'method' => 'post',
            'content' => [
                'entity' => 'xyz',
                'value'  => 'failedvalidate@sbi'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The selected entity is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithGstTaxInvoice' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
                'tax_invoice'       => [
                    'business_gstin'=> '123456789012345',
                    'gst_amount'    =>  10000,
                    'supply_type'   => 'intrastate',
                    'cess_amount'   =>  12500,
                    'customer_name' => 'Gaurav',
                    'number'        => '1234',
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'tax_invoice'       => [
                    'business_gstin'=> '123456789012345',
                    'gst_amount'    =>  10000,
                    'supply_type'   => 'intrastate',
                    'cess_amount'   =>  12500,
                    'customer_name' => 'Gaurav',
                    'number'        => '1234',
                ],
            ],
        ],
    ],
];

