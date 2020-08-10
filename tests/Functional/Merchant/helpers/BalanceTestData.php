<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

return [
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
                'free_payouts_count'   => "ier"
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
];
