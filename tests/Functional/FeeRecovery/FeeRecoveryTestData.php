<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateFeeRecoveryPayout' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutLowBalance' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'queued',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutSkipWorkflow' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testFeeRecoveryPayoutCron' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery/process',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success'   => true,
            ],
        ],
    ],

    'testProcessQueuedPayoutFeeRecoveryCreated' => [
        'request'  => [
            'url'    => '/payouts/queued/process',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testApprovePendingPayoutFeeRecoveryCreated' => [
        'request'  => [
            'method' => 'POST',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateFeeRecoveryScheduleTaskForMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],

    'testUpdateFeeRecoveryScheduleTaskForMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],

    'testCreateFeeRecoveryScheduleTaskForRecentlyActivatedMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],
];
