<?php

use RZP\Gateway\Netbanking\Canara\Mock\Server;
use RZP\Error\PublicErrorCode;
use RZP\Error\ErrorCode;
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
        'bank' => 'CNRB',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'netbanking_canara',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],
    'testPaymentNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 50000,
        'bank' => 'CNRB',
        'received' => true,
        'client_code' => 'abcom',
        'merchant_code' => 'test_merchant_id',
        'entity' => 'netbanking',
        'bank_payment_id' => 'AB1234',
    ],
    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => Server::BANK_REFERENCE_NUMBER,
        'received'        => true,
        'bank'            => 'CNRB',
    ],

];

