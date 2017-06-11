<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateVirtualAccount' => [
        'request' => [
            'url'     => '/virtual_accounts',
            'method'  => 'post',
            'content' => [
                'name'            => 'new virtual account',
                'descriptor'      => 'banana',
                'amount_expected' => 10000,
                'customer_id'     => 'cust_100000customer',
                'receiver_type'   => 'bank_account',
            ],
        ],
        'response' => [
            'content' => [
                'name'            => 'new virtual account',
                'descriptor'      => 'banana',
                'amount_expected' => 10000,
                'customer_id'     => 'cust_100000customer',
                'status'          => 'active',
            ],
        ],
    ],

    'testAccountCreditedWebhook' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.credited',
            'contains' => ['payment', 'bank_transfer'],
            'payload' => [
                'bank_transfer' => [
                    'entity' => [
                        'merchant_id' => "10000000000000",
                        'payer_account' => "9876543210123456789",
                        'payer_ifsc' => "HDFC0000001",
                        'payee_account' => "RAZORP14966082177614",
                        'payee_ifsc' => "YESB0CMSNOC",
                        'amount' => 5000000,
                        'mode' => "neft",
                        'description' => "NEFT payment of 50,000 rupees",
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 5000000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'method' => 'bank_transfer',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => true,
                        'description' => 'random description',
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'error_code' => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],
];
