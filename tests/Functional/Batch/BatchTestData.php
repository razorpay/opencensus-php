<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateBatchOfFundAccountType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'fund_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'fund_account',
                'status'      => 'created',
                'total_count' => 5,
            ],
        ],
    ],

    'testCreateBatchOfPayoutType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout',
                'otp'   => '0007',
                'token' => '12345678900000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'payout',
                'status'      => 'created',
                'total_count' => 2,
            ],
        ],
    ],

    'testCreateBatchOfFundAccountTypeRequestFileEntries' => [
        // Expected to create new bank account & new contact.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to create new bank account & new contact.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567891',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'P YV',
            'Contact Email'             => 'yv@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to use existing bank account.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to create a vpa and use existing contact.
        [
            'Fund Account Type'         => 'vpa',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => 'jitendrakkkk@upi',
            'Contact Id'                => 'cont_00000000000001',
            'Contact Type'              => '',
            'Contact Name'              => '',
            'Contact Email'             => '',
            'Contact Mobile'            => '',
            'Contact Reference Id'      => '',
            'notes[place]'              => '',
            'notes[code]'               => '',
        ],
        // Expected to create a vpa and new contact.
        [
            'Fund Account Type'         => 'vpa',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => 'jitendrakkkk@upi',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
    ],

    'testCreateBatchOfPayoutTypeRequestFileEntries' => [
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 100,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => '',
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 1000,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => 'fa_000000000test1',
            'Fund Account Type'         => '',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => '',
            'Contact Type'              => '',
            'Contact Name'              => '',
            'Contact Email'             => '',
            'Contact Mobile'            => '',
            'Contact Reference Id'      => '',
            'notes[place]'              => '',
            'notes[code]'               => '',
        ],
    ],
];
