<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateReserveBalanceInvalidAmount' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  50000,
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
                    'description' => 'Reserve Balance Amount should be greater than or equal to 100000',
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
                'amount'        =>  500000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
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
                'amount'        =>  500000,
                'type'          =>  'reserve_banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_banking balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
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
                'amount'        =>  500000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100xyz000xyz00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ]
];
