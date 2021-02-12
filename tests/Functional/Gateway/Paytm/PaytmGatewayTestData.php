<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'card',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paytm',
        'terminal_id'       => '1000PaytmTrmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],


    'testTransactionAfterAuthorize' => [
        'type'            => 'payment',
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'fee'             => 0,
        'pricing_rule_id' => null,
        'debit'           => 0,
        'credit'          => 0,
        'currency'        => 'INR',
        'balance'         => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'channel'         => 'axis',
        'settled'         => false,
        'settled_at'      => null,
        'settlement_id'   => null,
        'reconciled_at'   => null,
        'entity'          => 'transaction',
        'admin'           => true,
    ],

    'testTransactionAfterCapture' => [
        'type'          => 'payment',
        'merchant_id'   => '10000000000000',
        'amount'        => 50000,
        'fee'           => 1000,
        'debit'         => 0,
        'credit'        => 49000,
        'currency'      => 'INR',
        'balance'       => 1049000,
        'gateway_fee'   => 0,
        'api_fee'       => 0,
        'channel'       => 'axis',
        'settled'       => false,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity'        => 'transaction',
        'admin'         => true,
    ],

    'testPaymentPaytmEntity' => [
        'action'            => 'authorize',
        'received'          => true,
        'request_type'      => 'SEAMLESS',
        'method'            => 'card',
        'txn_amount'        => '500',
        'cust_id'           => 'a@b.com',
        'channel_id'        => 'WEB',
        'payment_mode_only' => 'Yes',
        'auth_mode'         => '3D',
        'bank_code'         => null,
        'payment_type_id'   => 'CC',
        'txnamount'         => '500',
        'status'            => 'TXN_SUCCESS',
        'respcode'          => '01',
        'respmsg'           => 'Txn Success',
        'paymentmode'       => 'CC',
        'refundamount'      => null,
        'gatewayname'       => 'INDB',
        'txntype'           => 'SALE',
        'refund_id'         => null,
        'entity'            => 'paytm',
    ],

    'testPaytmWallet' => [
        'merchant_id' => '10000000000000',
        'amount'      => 50000,
        'method'      => 'wallet',
        'wallet'      => 'paytm',
        'status'      => 'captured',
        'gateway'     => 'paytm',
        'terminal_id' => '1000PaytmTrmnl',
        'signed'      => false,
        'verified'    => null,
        'entity'      => 'payment',
    ],

    'testPaytmWalletEntity' => [
        'action'            => 'authorize',
        'received'          => true,
        'request_type'      => 'DEFAULT',
        'method'            => 'wallet',
        'txn_amount'        => '500',
        'cust_id'           => 'a@b.com',
        'channel_id'        => 'WEB',
        'payment_mode_only' => null,
        'auth_mode'         => null,
        'bank_code'         => null,
        'payment_type_id'   => null,
        'txnamount'         => '500',
        'status'            => 'TXN_SUCCESS',
        'respcode'          => '01',
        'respmsg'           => 'Txn Success',
        'paymentmode'       => 'PPI',
        'refundamount'      => null,
        'gatewayname'       => 'WALLET',
        'txntype'           => 'SALE',
        'refund_id'         => null,
        'entity'            => 'paytm',
    ],

    'testPayment3dsecureFailed' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
            'gateway_error_code'  => null
        ],
    ],

    'testPaymentInvalidHash' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Failed checksum verification",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'gateway_error_code'  => null
        ],
    ],

    'testPaymentAmountMismatch' => [
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
            'internal_error_code' => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentIdMismatch' => [
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
        ],
    ],

    'testPaytmWhenNotEnabled' => [
        'request' => [
            'url' => '/payments',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testRefundPayment' => [
        'action'            => 'refund',
        'received'          => true,
        'request_type'      => 'DEFAULT',
        'method'            => 'netbanking',
        'txn_amount'        => '500',
        'cust_id'           => 'a@b.com',
        'channel_id'        => 'WEB',
        'payment_mode_only' => 'Yes',
        'auth_mode'         => 'USRPWD',
        'bank_code'         => 'INDB',
        'payment_type_id'   => 'NB',
        'txnamount'         => null,
        'status'            => 'TXN_SUCCESS',
        'respcode'          => '01',
        'respmsg'           => 'Txn Successful.',
        'paymentmode'       => 'NB',
        'refundamount'      => '500.00',
        'gatewayname'       => 'INDB',
        'txntype'           => 'REFUND',
        'entity'            => 'paytm',
    ],
];
