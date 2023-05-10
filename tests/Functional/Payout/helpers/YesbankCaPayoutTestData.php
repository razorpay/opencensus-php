<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

return [

    'testCreatePayoutRtgs' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 20000000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'RTGS'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 20000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'RTGS',
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutUpi' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000003fa',
                'mode'            => 'UPI',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'YESBANK does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testDashboardSummary' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPayoutToAmexCardWithSupportedIssuerSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'mode'            => 'NEFT',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
            ],
        ],
    ],

    'testCreatePayoutImps' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS'
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
                'mode'            => 'IMPS',
                'notes'           => [],
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
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
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

    'testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'processing',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],
    'testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceLessThanPayoutAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'queued',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],

    'testCreateM2PPayoutForMerchantDirectAccountCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'ICICI does not support card payouts to CARD',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreateFreePayoutForNEFTModeDirectAccountProxyAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForIMPSModeDirectAccountPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForUPIModeDirectAccountPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'YESBANK does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreateFreePayoutForUPIModeDirectAccountWithUpiEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 200,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'UPI',
                'fund_account_id'       => '',
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 200,
                'currency'        => 'INR',
                'fund_account_id' => '',
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateNonFreePayoutForUPIModeDirectAccountWithUpiEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 200,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'UPI',
                'fund_account_id'       => '',
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 200,
                'currency'        => 'INR',
                'fund_account_id' => '',
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],
];
