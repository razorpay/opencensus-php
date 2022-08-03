<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
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
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'amex',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],
    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1500,
        'debit' => 0,
        'credit' => 48500,
        'currency' => 'INR',
        'balance' => 1048500,
        'gateway_fee' => 0,
        'api_fee' => 0,
//        'escrow_balance' => 1048275,
        'channel' => 'axis',
        'settled' => false,
//        'settled_at' => 1437589800,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity' => 'transaction',
        'admin' => true,
    ],

    'testPaymentAmexEntity' => [
//        'id' => 'pay_53',
//        'payment_id' => '3GZ95U9Rss628z',
        'genius' => false,
        'action' => 'capture',
        'received' => true,
        'amex' => true,
        'vpc_amount' => 50000,
//        'vpc_AcqResponseCode' => '14',
        'vpc_Command' => 'capture',
        'vpc_Currency' => 'INR',
//        'vpc_MerchTxnRef' => '3GZ95U9Rss628z',
        'vpc_3DSECI' => null,
//        'vpc_3DSXID' => null,
        'vpc_3DSenrolled' => null,
        'vpc_3DSstatus' => null,
        'vpc_AuthorizeId' => null,
        //'vpc_BatchNo' => '20150827', // '20150503',
        'vpc_Card' => 'MC',
        'vpc_ReceiptNo' => '511415585968',
//        'vpc_ShopTransactionNo' => '1100087478',
//        'vpc_TransactionNo' => '1100032024',
        'vpc_TxnResponseCode' => '0',
        'vpc_VerToken' => null,
        'vpc_VerType' => null,
        'vpc_VerSecurityLevel' => null,
        'vpc_VerStatus' => null,
        'vpc_Message' => 'Approved',
        'refund_id' => null,
        'entity' => 'amex',
    ],

    'testAmexCardWhenNotEnabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED
        ],
    ],

    'testAmexPricingCheckWhenEnablingAmex' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT
        ],
    ],

    'testFailureWhen3DSFailsForRiskyMerchant' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        ],
    ],

    'testFailureWhen3DSNotEnrolled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The card is not enrolled for American Express SafeKey program. Please try another card.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_AMEX_3DSECURE_AUTH_FAILED,
        ],
    ],

    'testPaymentVerify3DSFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
