<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateVirtualAccount' => [
        'name'            => 'New virtual account',
        'entity'          => 'virtual_account',
        'descriptor'      => 'banana',
        'amount_expected' => 10000,
        'status'          => 'active',
        'receiver_type'   => ['bank_account'],
        'bank_account'    => [
            'entity' => 'bank_account',
            'ifsc'   => 'RAZR0000001',
        ],
    ],

    'testFetchVirtualAccount' => [
        'name'            => 'New virtual account',
        'entity'          => 'virtual_account',
        'descriptor'      => 'banana',
        'amount_expected' => 10000,
        'status'          => 'active',
        'receiver_type'   => ['bank_account'],
        'bank_account'    => [
            'entity' => 'bank_account',
            'ifsc'   => 'RAZR0000001',
        ],
    ],

    'testFetchVirtualAccounts' => [
        'entity' => 'collection',
        'count'  => 2,
        'items'  => [
            [
                'name'            => 'Second VA',
                'entity'          => 'virtual_account',
                'descriptor'      => 'banana',
                'amount_expected' => 10000,
                'status'          => 'active',
            ],
            [
                'name'            => 'First VA',
                'entity'          => 'virtual_account',
                'descriptor'      => 'banana',
                'amount_expected' => 10000,
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
