<?php

return [
    'testManualAdjustmentCreateSuccess' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount' => 500,
                'type' => 'primary',
                'merchant_id' => '100abc000abc00',
                'currency' => 'INR',
                'description' => 'add primary balance in reverse shadow'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'adjustment',
                'amount' => 500,
                'currency' => 'INR',
                'description' => 'add primary balance in reverse shadow'
            ],
        ]
    ],

    'testAdjustmentTransactionCreate' => [
        'request' => [
            'url' => '/adjustments/transaction_create',
            'method' => 'POST',
            'content' => [
                'id'        =>  'LN1MS4fADj0Sn0',
                'transaction_id' =>  'LN5BWCGvLdPu7T',
            ]
        ],
        'response' => [
            'content' => [
                'entity'         => 'adjustment',
                'amount'         => 500,
                'currency'       => 'INR',
                'description'    =>  'add primary balance in reverse shadow',
                'transaction_id' => 'LN5BWCGvLdPu7T'
            ],
        ]
    ],

    'testManualAdjustmentCreateNonRetryableError' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount' => 500,
                'type' => 'primary',
                'merchant_id' => '100abc000abc00',
                'currency' => 'INR',
                'description' => 'add primary balance in reverse shadow'
            ]
        ],
        'response' => [
            "error_response" => [
                "code" => "BAD_REQUEST_ERROR",
                "description" => "record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXISTS",
                "source" => "NA",
                "step" => "NA",
                "reason" => "NA",
                "metadata" => []
            ],
            "http_status_code" => 400,
            "internal_error_code" => "BAD_REQUEST_ERROR"
        ],
    ],

    'testManualAdjustmentCreateRetryableError' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount' => 500,
                'type' => 'primary',
                'merchant_id' => '100abc000abc00',
                'currency' => 'INR',
                'description' => 'add primary balance in reverse shadow'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'adjustment',
                'amount' => 500,
                'currency' => 'INR',
                'description' => 'add primary balance in reverse shadow'
            ],
        ]
    ],


];




