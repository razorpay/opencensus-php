<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Adjustment\Validator;

return [
    'testCreateReserveBalanceInvalidAmount' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Reserve Balance Amount should be less than or equal to '
                        .Validator::MAX_RESERVE_BALANCE_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateReservePrimaryBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ],

    'testCreateReserveBankingBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_banking balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_banking balance add',
            ],
        ]
    ],

    'testAddReserveBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100xyz000xyz00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ]
];
