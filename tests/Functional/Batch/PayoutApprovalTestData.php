<?php

use RZP\Models\Batch\Header;

return [
    'testBatchFileValidation' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_approval'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 2,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::APPROVE_REJECT_PAYOUT => 'A',
                        Header::P_A_AMOUNT => 100.23,
                        Header::P_A_CURRENCY => 'INR',
                        Header::P_A_CONTACT_NAME => 'rakshit',
                        Header::P_A_MODE => 'IMPS',
                        Header::P_A_PURPOSE => 'payout',
                        Header::P_A_PAYOUT_ID => 'pout_FEdazBZrwR6Bn4',
                        Header::P_A_CONTACT_ID => 'FEcrWGP1ZAEU5S',
                        Header::P_A_FUND_ACCOUNT_ID => 'FEcsAwD6TwN7gd',
                        Header::P_A_CREATED_AT => null,
                        Header::P_A_ACCOUNT_NUMBER => 7878780019112500,
                        Header::P_A_STATUS => 'pending',
                        Header::P_A_NOTES => '[]',
                        Header::P_A_FEES => 0,
                        Header::P_A_TAX =>0,
                        Header::P_A_SCHEDULED_AT => NULL
                    ],
                ],
                'approved_count' => 1,
                'rejected_count' => 1,
                'approved_amount' => 100.23,
                'rejected_amount' => 100
            ],
        ]
    ],

    'testBatchFileValidationFailure' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_approval'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => [],
                'approved_count'    => 0,
                'rejected_count'    => 0,
                'approved_amount'   => 0,
                'rejected_amount'   => 0
            ]
        ]
    ],

    'testPayoutBatchFileValidation' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 2,
                'error_count'       => 0,
                'parsed_entries'    => [

                ],
                'total_payout_amount'     => 500
            ],
        ]
    ],
];
