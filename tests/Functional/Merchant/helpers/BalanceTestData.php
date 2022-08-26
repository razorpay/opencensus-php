<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateCapitalBalance' => [
        'request'  => [
            'url'     => '/capital_balances',
            'method'  => 'post',
            'content' => [
                'merchant_id'   => '10000000000000',
                'type'          => 'principal',
                'currency'      => 'INR',
                'balance'       => 1000,
            ]
        ],
        'response' => [
            'content' => [
                'balance'           => 1000,
                'type'              => 'principal',
                'currency'          => 'INR',
                'merchant_id'       => '10000000000000',
                // 'id'                => 'G36eVO6FseJ35v',
                // 'updated_at'        => 1605806235,
                'last_fetched_at'   => null,
            ],
        ],
    ],

    'testCreateCapitalBalanceWithDefaultBalance' => [
        'request'  => [
            'url'     => '/capital_balances',
            'method'  => 'post',
            'content' => [
                'merchant_id'   => '10000000000000',
                'type'          => 'principal',
                'currency'      => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'balance'           => 0,
                'type'              => 'principal',
                'currency'          => 'INR',
                'merchant_id'       => '10000000000000',
                // 'id'                => 'G36eVO6FseJ35v',
                // 'updated_at'        => 1605806235,
                'last_fetched_at'   => null,
            ],
        ],
    ],

    'testCreateCapitalBalanceWithNegativeBalance' => [
        'request'  => [
            'url'     => '/capital_balances',
            'method'  => 'post',
            'content' => [
                'merchant_id'   => '10000000000000',
                'type'          => 'principal',
                'currency'      => 'INR',
                'balance'       => -1000,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The balance must be at least 0.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetBalance' => [
        'request'  => [
            'url'     => '/internal_balances/',
            'method'  => 'get',
            'content' => [
                'merchant_id'   => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'balance'           => 1000,
                'type'              => 'principal',
                'currency'          => 'INR',
                // 'id'                => 'G36eVO6FseJ35v',
                // 'updated_at'        => 1605806235,
                'last_fetched_at'   => null,
            ],
        ],
    ],

    'testGetBalanceForGraphQL' => [
        'request'  => [
            'url'     => '/balance/{id}',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'balance'           => 900,
                'type'              => 'primary',
                'currency'          => 'INR',
                // 'id'                => 'G36eVO6FseJ35v',
                // 'updated_at'        => 1605806235,
                'last_fetched_at'   => null,
            ],
        ],
    ],

    'testGetBalanceMultiple' => [
        'request' => [
            'url'     => '/internal_balances_multiple',
            'method'  => 'get',
            'content' => [
                'ids' => [], // filled while running the test
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    // asserted in test
                ]
            ],
        ],
    ],

    'testUpdateFreePayoutsCount' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'            => 12,
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_count'            => '12'
            ],
        ],
    ],

    'testUpdateFreePayoutsCountAndMode' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'            => 12,
                'free_payouts_supported_modes'   => ['IMPS']
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_count'            => '12',
                'free_payouts_supported_modes'   => ['IMPS']
            ],
        ],
    ],

    'testUpdateFreePayoutsMode' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_supported_modes'   => ['IMPS']
            ]
        ],
        'response' => [
            'content' => [
                'free_payouts_supported_modes'   => ['IMPS']
            ],
        ],
    ],

    'testFailUpdateFreePayoutsWithDuplicateModeInArray' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_supported_modes'   => ['IMPS', 'IMPS']
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Value in free payout supported modes array is duplicate.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FREE_PAYOUT_SUPPORTED_MODES_ARRAY_DUPLICATE_VALUE,
        ],
    ],

    'testFailUpdateFreePayoutsWithInvalidModeInArray' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_supported_modes'   => ['IMPS', 'hjks']
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout mode is invalid'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
        ],
    ],

    'testFailUpdateFreePayoutsWithoutModeAndCount' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either one of free_payouts_count or free_payouts_supported_modes or both should be given.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateFreePayoutsWithModeArrayEmpty' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_supported_modes'   => []
            ]
        ],
        'response'  => [
            'content' => [
                'free_payouts_supported_modes'   => []
            ],
        ],
    ],

    'testUpdateFreePayoutsWithModeArrayNull' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_supported_modes'   => null
            ]
        ],
        'response'  => [
            'content' => [
                'free_payouts_supported_modes'   => []
            ],
        ],
    ],

    'testFailUpdateFreePayoutsWithInvalidCount' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'   => "ier"
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The free payouts count must be an integer.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFailUpdateFreePayoutsWithInvalidBalanceId' => [
        'request'  => [
            'url'     => '/balance/gsdglddggjlgldjdlg/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'   => 12
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'gsdglddggjlgldjdlg is not a valid id'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFailUpdateFreePayoutsWithInvalidBalanceType' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'   => 12
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only Banking type balance is allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INCORRECT_BALANCE_TYPE,
        ],
    ],

    'testFailUpdateFreePayoutsWithNegativeCount' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'   => -20,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The free payouts count must be at least 0.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFailUpdateFreePayoutsWithBalanceIdNotPresentInDb' => [
        'request'  => [
            'url'     => '/balance/{id}/free_payout',
            'method'  => 'post',
            'content' => [
                'free_payouts_count'   => 12
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid balance id, no db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID,
        ],
    ],

    'testGetBalancesForBalanceIds' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/internal_balances_queued',
            'content' => [
                'balance_ids' => [

                ]
            ],
        ],
        'response' => [
            'content' => [
                'balances' => [

                ]
            ],
        ],
    ],

    'testGetBalanceForMerchantIds' => [
        'request' => [
            'url'     => '/internal_balances',
            'method'  => 'get',
            'content' => [
                'merchant_ids' => [
                    '10000000000000',
                    '10000000000001',
                ],
                'balance_type' => 'primary'
            ],
        ],
        'response' => [
            'content' => [
                'balances'=>[
                        [
                            'merchant_id' => '10000000000000',
                            'balance'     => 1000000,
                        ],
                        [
                            'merchant_id' => '10000000000001',
                            'balance'     => 200000,
                        ],
                    ]
            ],
        ],
    ],

];
