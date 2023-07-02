<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code',
            'notes'          => [
                'key' => 'value',
            ],
        ],
    ],

    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'upi',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'upi_mindgate',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testIntentPaymentWithVpa' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The vpa field is not required and not shouldn\'t be sent.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUpiAmountCap' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount for UPI payment cannot be greater than ₹200000.00',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
                'bank'           => 'RATN',
                'account_number' => '04030403040304',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
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

    'testVpaWithoutPspValidation' => [
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
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA
        ],
    ],

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

    'testVerificationFailure' => [
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

    'testCollectRejectedFailureUnknownRespCode' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ]
    ],

    'testValidateVpaSuccess' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@okhdfcbank',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'success@okhdfcbank',
                'success'       => true,
                'customer_name' => 'User Name',
            ],
        ]
    ],

    'testValidateVpaFailure' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'invalidvpa@hdfcbank',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'           => 'invalidvpa@hdfcbank',
                'success'       => false,
                'customer_name' => null,
            ],
        ]
    ],

    'testUnexpectedPaymentSuccess' => [
        'pgMerchantId' => 'razorpay upi mindgate',
        'meRes' => '1861365267|paysucc123|2279.24|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|NA|NA'
    ],

    'testUnexpectedPaymentWithPayerAccountTypeSuccess' => [
        'pgMerchantId' => 'razorpay upi mindgate',
        'meRes' => '1861365267|paysucc123|2279.24|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|SAVINGS!NA!NA!NA!NA|NA'
    ],

    'testUnexpectedPaymentWithInvalidPayerAccountTypeSuccess' => [
        'pgMerchantId' => 'razorpay upi mindgate',
        'meRes' => '1861365267|paysucc123|2279.24|2018:09:18 03:02:15|SUCCESS|Transaction success|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|INVALID!NA!NA!NA!NA|NA'
    ],

    'testUnexpectedPaymentFail' => [
        'pgMerchantId' => 'razorpay upi mindgate',
        'meRes' => '1861365267|'. str_random(12) .'|378.00|2018:09:18 03:02:15|FAILURE|FAILURE|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|NA|NA'
    ],

    'testDirectSettlementUnexpectedPaymentFail' => [
        'pgMerchantId' => 'direct settlement mindgate',
        'meRes' => '1861365267|'. str_random(12) .'|378.00|2018:09:18 03:02:15|FAILURE|FAILURE|00|NA|7013562166@okhdfcbank|826115528405|NA|null|null|null|null|null|State Bank Of India!00000020261329233!SBIN0014823!917013562166|PAY!http://www.npci.co.in!NA!YESB762207F7C3CC5D93E05400144FF8FAF!NA!|bookmyshow.rzp@hdfcbank!NA!NA|NA|NA'
    ],

    'testInitiateIntentTpvFailedPayment' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Payment was unsuccessful due to a temporary issue. If amount got deducted, it will be refunded within 5-7 working days.'
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ]
    ],
    'testCreateUpiQRVirtualAccountMultipleUsage' => [
        'request' => [
            'url'     => '/virtual_accounts',
            'method'  => 'POST',
            'content' => [
                "usage"=> "multiple_use",
                "description"=> "QR Description",
                "name"=> "TestName",
                "notes"=> [
                    "test"=> "Notes",
                    "test2"=> "Notes2"
                ],
                "receivers"=> [
                    "types"=> [
                        "qr_code"
                      ],
                    "qr_code"=> [
                        "method"=> [
                            "card"=> false,
                            "upi"=> true,
                        ]
                    ]
                ]
                             ],
            ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ]
        ],
    'testCreateUpiQRVirtualAccountSingleUsage' => [
        'request' => [
            'url'     => '/virtual_accounts',
            'method'  => 'POST',
            'content' => [
                "usage"=> "single_use",
                "description"=> "QR Description",
                "name"=> "TestName",
                "notes"=> [
                    "test"=> "Notes",
                    "test2"=> "Notes2"
                ],
                "receivers"=> [
                    "types"=> [
                        "qr_code"
                    ],
                    "qr_code"=> [
                        "method"=> [
                            "card"=> false,
                            "upi"=> true,
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ]
    ],

    'testCreateUpiQRVirtualAccount' => [
        'request' => [
            'url'     => '/virtual_accounts',
            'method'  => 'POST',
            'content' => [
                "usage"=> "single_use",
                "description"=> "QR Description",
                "name"=> "TestName",
                "notes"=> [
                    "test"=> "Notes",
                    "test2"=> "Notes2"
                ],
                "receivers"=> [
                    "types"=> [
                        "qr_code"
                    ],
                    "qr_code"=> [
                        "method"=> [
                            "card"=> false,
                            "upi"=> true,
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ]
    ],

    'testCreateUpiQRVirtualAccountWithCloseBy' => [
        'request' => [
            'url'     => '/virtual_accounts',
            'method'  => 'POST',
            'content' => [
                "usage"=> "single_use",
                "description"=> "QR Description",
                "name"=> "TestName",
                "notes"=> [
                    "test"=> "Notes",
                    "test2"=> "Notes2"
                ],
                "receivers"=> [
                    "types"=> [
                        "qr_code"
                    ],
                    "qr_code"=> [
                        "method"=> [
                            "card"=> false,
                            "upi"=> true,
                        ]
                    ]
                ]
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ]
    ],
];
