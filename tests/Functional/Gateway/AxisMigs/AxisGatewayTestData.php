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
        'gateway' => 'axis_migs',
        'terminal_id' => '1000AxisMigsTl',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
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

    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1000,
        'debit' => 0,
        'credit' => 49000,
        'currency' => 'INR',
        'balance' => 1049000,
        'gateway_fee' => 0,
        'api_fee' => 0,
//        'escrow_balance' => 1048850,
        'channel' => 'kotak',
        'settled' => false,
//        'settled_at' => 1437589800,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity' => 'transaction',
        'admin' => true,
    ],

    'testPaymentAxisMigsEntity' => [
//        'id' => 'pay_53',
//        'payment_id' => '3GZ95U9Rss628z',
        'genius' => false,
        'action' => 'authorize',
        'received' => true,
        'vpc_amount' => 50000,
        'vpc_AcqResponseCode' => '14',
        'vpc_Command' => 'pay',
        'vpc_Currency' => 'INR',
//        'vpc_MerchTxnRef' => '3GZ95U9Rss628z',
        'vpc_3DSECI' => '01',
//        'vpc_3DSXID' => null,
        'vpc_3DSenrolled' => 'Y',
        'vpc_3DSstatus' => 'Y',
        'vpc_AuthorizeId' => null,
        //'vpc_BatchNo' => '20150827', // '20150503',
        'vpc_Card' => 'MC',
        'vpc_ReceiptNo' => '511415585968',
//        'vpc_ShopTransactionNo' => '1100087478',
//        'vpc_TransactionNo' => '1100032024',
        'vpc_TxnResponseCode' => '0',
//        'vpc_VerToken' => null,
        'vpc_VerType' => '3DS',
        'vpc_VerSecurityLevel' => '06', // null,
        'vpc_VerStatus' => 'M', //null,
        'vpc_Message' => 'Accepted', // 'Approved',
        'refund_id' => null,
        'entity' => 'axis_migs',
        'terminal_id' => '1000AxisMigsTl',
    ],

    'testPaymentAxisMigsCaptureEntity' => [
//        'id' => 'pay_53',
//        'payment_id' => '3GZ95U9Rss628z',
        'genius' => false,
        'action' => 'capture',
        'received' => true,
        'vpc_amount' => 50000,
        'vpc_AcqResponseCode' => '00',
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
//        'vpc_VerToken' => null,
        'vpc_VerType' => null,
        'vpc_VerSecurityLevel' => null, // null,
        'vpc_VerStatus' => null, //null,
        'vpc_Message' => 'Approved',
        'refund_id' => null,
        'entity' => 'axis_migs',
        'terminal_id' => '1000AxisMigsTl',
    ],

    'testPaymentRefund' => [
//        'id' => '153',
//        'payment_id' => '3dxwY5ZgxBnrQE',
        'genius' => false,
        'action' => 'refund',
        'received' => true,
//        'vpc_Amount' => 50000,
        'vpc_AcqResponseCode' => '00',
        'vpc_Command' => 'refund',
        'vpc_Currency' => 'INR',
//        'vpc_MerchTxnRef' => '3dxwY5ZgxBnrQE',
        'vpc_3DSECI' => null,
        'vpc_3DSXID' => null,
        'vpc_3DSenrolled' => null,
        'vpc_3DSstatus' => null,
        'vpc_AuthorizeId' => null,
//        'vpc_BatchNo' => '20150503',
        'vpc_Card' => 'MC',
        // 'vpc_ReceiptNo' => '511415585968',
        // 'vpc_ShopTransactionNo' => '1100074051',
        // 'vpc_TransactionNo' => '1100038634',
        'vpc_TxnResponseCode' => '0',
        'vpc_VerToken' => null,
        'vpc_VerType' => null,
        'vpc_VerSecurityLevel' => null,
        'vpc_VerStatus' => null,
        'vpc_Message' => 'Approved',
        // 'refund_id' => '3dxwYEv4SMXyE5',
        // 'created_at' => 1437872024,
        // 'updated_at' => 1437872024,
        'vpc_amount' => 50000,
        'entity' => 'axis_migs',
        'terminal_id' => '1000AxisMigsTl',
        'admin' => true,
    ],

    'testRecurringPaymentAuthenticateCard' => [
        'amount'              => 50000,
        'method'              => 'card',
        'status'              => 'authorized',
        'order_id'            => null,
        'international'       => false,
        'amount_refunded'     => 0,
        'refund_status'       => null,
        'currency'            => 'INR',
        'bank'                => null,
        'wallet'              => null,
        'internal_error_code' => null,
        'customer_id'         => 'cust_100000customer',
        'global_customer_id'  => null,
        'app_token'           => null,
        'global_token_id'     => null,
        'email'               => 'a@b.com',
        'contact'             => '+919918899029',
        'transaction_id'      => null,
        'auto_captured'       => false,
        'gateway'             => 'axis_migs',
        'recurring'           => true,
        'late_authorized'     => false,
        'captured'            => false,
        'entity'              => 'payment',
        'admin'               => true
    ],

    'recurringEntity' => [
        'action'                => 'authorize',
        'received'              => true,
        'genius'                => false,
        'amex'                  => false,
        'vpc_Amount'            => 50000,
        'vpc_AcqResponseCode'   => '00',
        'vpc_AuthorisedAmount'  => null,
        'vpc_CapturedAmount'    => null,
        'vpc_Command'           => 'pay',
        'vpc_Currency'          => 'INR',
        'vpc_3DSECI'            => null,
        'vpc_3DSXID'            => null,
        'vpc_3DSenrolled'       => null,
        'vpc_3DSstatus'         => null,
        'vpc_Card'              => 'MC',
        'vpc_ReceiptNo'         => '713116320780',
        'vpc_RefundedAmount'    => null,
        'vpc_ShopTransactionNo' => null,
        'vpc_TxnResponseCode'   => '0',
        'vpc_VerToken'          => null,
        'vpc_VerType'           => null,
        'vpc_VerSecurityLevel'  => null,
        'vpc_VerStatus'         => null,
        'vpc_Message'           => 'Accepted',
        'vpc_CSCResultCode'     => 'Unsupported',
        'vpc_AcqCSCRespCode'    => 'Unsupported',
        'refund_id'             => null,
        'terminal_id'           => 'MiGSRcgTmlN3DS',
        'entity'                => 'axis_migs',
    ],

    'testMaestroOnMigsFailOnLive' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testPaymentVerifyFailed' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testFailureWhen3DSFailsForDomesticMerchant' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'International card is not allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED,
            'twoFaError' => true,
        ],
    ],

    'testFailureWhen3DSFailsForRiskyMerchant' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment processing failed by bank due to risk',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        ],
    ],

    'testInvalidAmaCaptureError' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
            'gateway_error_code'  => '7',
        ],
    ],

    'testCaptureError' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,
            'gateway_error_code'  => '7',
        ],
    ],

    'testFailedPaymentWithProperError' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '5200000000000064',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK,
            'gateway_error_code'  => '3',
        ],
    ],

    'testVerifyRefundOldRefund' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
            'description' => 'Unable to verify migs refund'
        ],
    ],

    'testVerifyRefundFailedOnGatewayMultipleResponses' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
            'description' => 'Unable to verify migs refund'
        ],
    ],
];
