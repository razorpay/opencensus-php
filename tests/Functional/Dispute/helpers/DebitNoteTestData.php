<?php

return [
    'testBatchCreate' => [
        'request'  => [
            'url'     => '/debit_note/batch',
            'content' => [
                'merchant_id'     => '10000000000000',
                'payment_ids'     => 'pay_randomPayId123, pay_randomPayId456',
                'skip_validation' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'input_payment_ids' => 'pay_randomPayId123, pay_randomPayId456',
                'error_description' => '',
            ],
        ],

    ],

    'testBatchCreateMobileSignup' => [
        'request'  => [
            'url'     => '/debit_note/batch',
            'content' => [
                'merchant_id'     => '10000000000000',
                'payment_ids'     => 'pay_randomPayId123, pay_randomPayId456',
                'skip_validation' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'input_payment_ids' => 'pay_randomPayId123, pay_randomPayId456',
                'error_description' => '',
            ],
        ],

    ],

    'testCreateDebitNoteWithDisputeInInvalidStatusShouldFail' => [
        'request'  => [
            'url'     => '/debit_note/batch',
            'content' => [
                'merchant_id'     => '10000000000000',
                'payment_ids'     => 'pay_randomPayId123',
                'skip_validation' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'input_payment_ids' => 'pay_randomPayId123',
                'debit_note_id'     => '',
                'error_description' => 'pay_randomPayId123 in input not in relevant status to create debit note',
            ],
        ],
    ],

    'testCreateDuplicateDebitNoteForPaymentShouldFail' => [
        'request'  => [
            'url'     => '/debit_note/batch',
            'content' => [
                'merchant_id'     => '10000000000000',
                'payment_ids'     => 'pay_randomPayId123',
                'skip_validation' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'input_payment_ids' => 'pay_randomPayId123',
                'debit_note_id'     => '',
                'error_description' => 'One of the payments already has a debit note against it',
            ],
        ],
    ],

    'testCreateDebitNoteWithSufficientMerchantBalanceShouldFail' => [
        'request'  => [
            'url'     => '/debit_note/batch',
            'content' => [
                'merchant_id'     => '10000000000000',
                'payment_ids'     => 'pay_randomPayId123',
                'skip_validation' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'input_payment_ids' => 'pay_randomPayId123',
                'debit_note_id'     => '',
                'error_description' => 'randomPayId123 can directly be debited from merchant balance',
            ],
        ],
    ],
];