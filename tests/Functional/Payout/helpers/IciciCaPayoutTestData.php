<?php
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

return [
    'testIciciAccountStatementFetchV2' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'icici',
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici'
            ],
        ],
    ],

    'testCreatingPendingPayoutsForIciciWithSupportedModeChannelDestinationTypeCombo' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => '1000000',
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'status'          => 'pending',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
            ],
        ],
    ],

    'testCreatingPendingPayoutsForIciciWithUnsupportedModeChannelDestinationTypeCombo' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_D6XkDQaM3whg5v',
                'amount'          => '100',
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'ICICI does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayout' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'notes'           => [],
            ],
        ],
    ],

    'testBulkApprovePayoutWithComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'content' => [
                'payout_ids'   => [],
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Bulk Approving'
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],
];
