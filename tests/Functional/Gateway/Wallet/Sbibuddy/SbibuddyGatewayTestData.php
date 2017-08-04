<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;

return [
    'testPayment'   => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'captured',
        'two_factor_auth'   => 'passed',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'sbibuddy',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_sbibuddy',
        'terminal_id'       => '1000SbibdyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],
    'testPaymentWalletEntity' => [
        'action'               => 'authorize',
        // They give the response in Rupees
        'amount'               => 500,
        'wallet'               => 'sbibuddy',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '9918899029',
        'status_code'          => ResponseCodeMap::SUCCESS_CODE,
        'entity'               => 'wallet'
    ],
    'testRefundPayment' => [
        'action'               => 'refund',
        'wallet'               => 'sbibuddy',
        'email'                => 'a@b.com',
        'amount'               => 50000,
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '000',
        'response_code'        => 'SUCCESS',
        'response_description' => 'APPROVED',
        'entity'               => 'wallet'
    ],
];
