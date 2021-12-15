<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'netbanking',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'card_id' => null,
        'bank' => 'KKBK',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'netbanking_kotak',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OSKOTAK',
        'entity' => 'netbanking',
    ],

    'testPaymentNewNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OSIND',
        'entity' => 'netbanking',
    ],

    'testPaymentNewNetbankingEntity1' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OSRAZORPAY',
        'entity' => 'netbanking',
    ],

    'testPaymentTpvNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OTTEST',
        'entity' => 'netbanking',
    ],

    'testPaymentTpvNewNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OTIND',
        'entity' => 'netbanking',
    ],

    'testPaymentTpvNewNetbankingEntity1' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'KKBK',
        'received' => true,
        'merchant_code' => 'OTRAZORPAY',
        'entity' => 'netbanking',
    ],

    'testPaymentOnSharedTerminal' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'netbanking',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'card_id' => null,
        'bank' => 'KKBK',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'netbanking_hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
        'terminal_id' => '100NbHdfcTrmnl',
    ],

    'testAmountTampering' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testVerifyCallbackFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment has been cancelled. Try again or complete the payment later.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testFailedPaymentVerifyCallback' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testVerifyFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Failed checksum verification',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ]
];
