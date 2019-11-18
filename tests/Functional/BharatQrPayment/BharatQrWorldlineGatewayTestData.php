<?php

use  Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Gateway\Worldline\Fields;
use RZP\Models\Currency\Currency;
use RZP\Error\PublicErrorDescription;

return [

    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code'
        ],
    ],

    'testQrPaymentProcess' => [
        'url'     => '/payment/callback/bharatqr/worldline',
        'method'  => 'post',
        'content' => [
            Fields::MID                 => '037122003842039',
            Fields::M_PAN               => '4604901004774122',
            Fields::CUSTOMER_NAME       => 'Vishnu',
            Fields::TXN_CURRENCY        => Currency::INR,
            Fields::TXN_AMOUNT          => '200.00',
            Fields::AUTH_CODE           => 'AUTH',
            Fields::REF_NO              => '721304414190',
            Fields::PRIMARY_ID          => 'tobeset',
            Fields::SECONDARY_ID        => 'CARD',
            Fields::SETTLEMENT_AMOUNT   => '200.00',
            Fields::TIME_STAMP          => '20170801093103',
            Fields::TRANSACTION_TYPE    => '1',
            Fields::BANK_CODE           => '00031',
            Fields::AGGREGATOR_ID       => 'AG1',
            Fields::CONSUMER_PAN        => '438628xxxxxx3456',
        ],
    ],

    'testUpiQrPaymentProcess' => [
        'url'     => '/payment/callback/bharatqr/worldline',
        'method'  => 'post',
        'content' => [
            Fields::TXN_CURRENCY        => Currency::INR,
            Fields::TXN_AMOUNT          => '200.00',
            Fields::REF_NO              => '721304414190',
            Fields::PRIMARY_ID          => 'tobeset',
            Fields::SECONDARY_ID        => 'UPIT',
            Fields::SETTLEMENT_AMOUNT   => '200.00',
            Fields::TIME_STAMP          => '20170801093103',
            Fields::TRANSACTION_TYPE    => '2',
            Fields::BANK_CODE           => '00031',
            Fields::AGGREGATOR_ID       => 'AG1',
            Fields::MID                 => '037122003842039',
            Fields::MERCHANT_VPA        => 'razorpay@axis',
            Fields::CUSTOMER_VPA        => 'vishnu@icici',
        ],
    ],

    'testWorldlineRefundEntity' => [
        'action'       => 'refund',
        'txn_amount'   => '20000',
        'txn_currency' => 'INR',
        'entity'       => 'worldline',
        'admin'        => true,
    ],

    'testWorldlinePaymentAuthorizeEntity' => [
        'action'           => 'authorize',
        'received'         => false,
        'mid'              => '037122003842039',
        'txn_currency'     => 'INR',
        'txn_amount'       => '200.00',
        'auth_code'        => 'AUTH',
        'ref_no'           => '721304414190',
        'transaction_type' => 'CARD',
        'bank_code'        => '00031',
        'aggregator_id'    => 'AG1',
        'customer_vpa'     => '',
        'entity'           => 'worldline',
        'admin'            => true,
    ],

    'testWorldlineUpiPaymentAuthorizeEntity' => [
        'action'           => 'authorize',
        'received'         => false,
        'mid'              => '037122003842039',
        'txn_currency'     => 'INR',
        'txn_amount'       => '200.00',
        'auth_code'        => '',
        'ref_no'           => '721304414190',
        'transaction_type' => 'UPI',
        'bank_code'        => '00031',
        'aggregator_id'    => 'AG1',
        'customer_vpa'     => 'vishnu@icici',
        'entity'           => 'worldline',
        'admin'            => true,
    ],

    'testVerifyInvalidResponse' => [
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
            'class'                 => 'RZP\Exception\RuntimeException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testWorldlinePaymentFail' => [
        'response'  => [
            'content'     => [
                'status'    => 'Failure',
                'errorMsg'  => 'Not a supported action',
            ],
            'status_code' => 200,
        ]
    ],
];
