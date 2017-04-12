<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'currency'        => 'INR',
        'base_amount'     => 50000,
        'status'          => 'authorized',
        'two_factor_auth' => 'passed',
        'method'          => 'wallet',
        'wallet'          => 'mpesa',
        'gateway'         => 'wallet_mpesa',
        'terminal_id'     => '100VodaMpesaTl',
    ],

    'testPaymentWalletEntity' => [
        'action'   => 'otp_generate',
        'received' => true,
        'wallet'   => 'mpesa'
    ],
];
