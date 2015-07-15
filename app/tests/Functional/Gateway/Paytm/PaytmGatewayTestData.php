<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

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
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'paytm',
        'terminal_id' => '1000PaytmTrmnl',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentPaytmEntity' => [
        'action' => 'authorize',
        'request_type' => 'SEAMLESS',
        'method' => 'card',
        'txn_amount' => '500',
        'cust_id' => 'a@b.com',
        'channel_id' => 'WEB',
        'payment_mode_only' => 'Yes',
        'auth_mode' => '3D',
        'bank_code' => null,
        'payment_type_id' => 'DC',
        'txnamount' => '500',
        //'banktxnid' => '201506080000004',
        //'orderid' => '3KvJj9YNa60mBt',
        'status' => 'TXN_SUCCESS',
        'respcode' => '01',
        'respmsg' => 'Txn Success',
        //'bankname' => 'Axis Bank',
        'paymentmode' => 'DC',
        'refundamount' => null,
        'gatewayname' => 'ICICI',
        //'txndate' => '2015-06-08 03-28-34.0',
        'txntype' => 'SALE',
        'refund_id' => null,
        'entity' => 'paytm',
    ],

    'testPaytmWallet' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'wallet',
        'wallet' => 'paytm',
        'status' => 'captured',
        'gateway' => 'paytm',
        'terminal_id' => '1000PaytmTrmnl',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaytmWalletEntity' => [
        'action' => 'authorize',
        'request_type' => 'DEFAULT',
        'method' => 'wallet',
        'txn_amount' => '500',
        'cust_id' => 'a@b.com',
        'channel_id' => 'WEB',
        'payment_mode_only' => null,
        'auth_mode' => null,
        'bank_code' => null,
        'payment_type_id' => null,
        'txnamount' => '500',
        //'banktxnid' => '201506080000004',
        //'orderid' => '3KvJj9YNa60mBt',
        'status' => 'TXN_SUCCESS',
        'respcode' => '01',
        'respmsg' => 'Txn Success',
        //'bankname' => 'Axis Bank',
        'paymentmode' => 'PPI',
        'refundamount' => null,
        'gatewayname' => 'WALLET',
        //'txndate' => '2015-06-08 03-28-34.0',
        'txntype' => 'SALE',
        'refund_id' => null,
        'entity' => 'paytm',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => null
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT
        ],
    ],

    'testRefundPayment' => [
        'action' => 'refund',
        'request_type' => 'DEFAULT',
        'method' => 'netbanking',
        'txn_amount' => '500',
        'cust_id' => 'a@b.com',
        'channel_id' => 'WEB',
        'payment_mode_only' => 'Yes',
        'auth_mode' => 'USRPWD',
        'bank_code' => 'ICICI',
        'payment_type_id' => 'NB',
        'txnamount' => null,
        //'banktxnid' => '201506080000004',
        //'orderid' => '3KvJj9YNa60mBt',
        'status' => 'TXN_SUCCESS',
        'respcode' => '01',
        'respmsg' => 'Txn Successful.',
        //'bankname' => 'Axis Bank',
        'paymentmode' => 'NB',
        'refundamount' => '500',
        'gatewayname' => 'ICICI',
        //'txndate' => '2015-06-08 03-28-34.0',
        'txntype' => 'REFUND',
        // 'refund_id' => null,
        'entity' => 'paytm',
    ],
];