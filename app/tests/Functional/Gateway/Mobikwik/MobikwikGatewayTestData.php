<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testPayment'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
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
        'contact'           => '9918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'mobikwik',
        'terminal_id'       => '1000MobiKwikTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],

    'testPaymentMobikwikEntity' => [
        'action'        => 'authorize',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => '500',
        'cell'          => '9918899029',
        'showmobile'    => null,
        'statuscode'    => '0',
        'statusmessage' => 'Transaction completed Successfully',
//        'refid',
//        'ispartial'
        'refund_id'     => null,
        'entity'        => 'mobikwik',
    ],

    'testMobikwikWallet'        => [
        'merchant_id' => '10000000000000',
        'amount'      => 50000,
        'method'      => 'wallet',
        'wallet'      => 'mobikwik',
        'status'      => 'captured',
        'gateway'     => 'mobikwik',
        'terminal_id' => '1000MobiKwikTl',
        'signed'      => false,
        'verified'    => null,
        'entity'      => 'payment',
    ],

    'testMobikwikWalletEntity'  => [
        'action'        => 'authorize',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => '500',
        'cell'          => '9918899029',
        'showmobile'    => null,
        'statuscode'    => '0',
        'statusmessage' => 'Transaction completed Successfully',
//        'refid',
//        'ispartial'
        'refund_id'     => null,
        'entity'        => 'mobikwik',
    ],

    'testPayment3dsecureFailed' => [
        'request'   => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
            'gateway_error_code'  => null
        ],
    ],

    'testPaytmWhenNotEnabled'   => [
        'request'   => [
            'url'     => '/payments',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT
        ],
    ],

    'testRefundPayment'         => [
        'action'        => 'refund',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => '500',
        'cell'          => null,
        'showmobile'    => null,
        'statuscode'    => '0',
        'statusmessage' => 'Some message',
//        'refid',
//        'ispartial'
//        'refund_id' => null,
        'entity'        => 'mobikwik',
    ],
];