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

    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1150,
        'debit' => 0,
        'credit' => 48850,
        'currency' => 'INR',
        'balance' => 1048850,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'escrow_balance' => 1048850,
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
        'vpc_Currency' => null,
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
        'vpc_Currency' => null,
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
    ]
];
