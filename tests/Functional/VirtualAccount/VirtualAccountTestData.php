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

    'testWebhookOnVirtualAccountPay' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'payment.captured',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 10000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'order_id' => null,
                        'invoice_id' => null,
                        'method' => 'bank_transfer',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => true,
                        'description' => null,
                        'email' => null,
                        'contact' => null,
                        'error_code' => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],
];
