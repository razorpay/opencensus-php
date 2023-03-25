<?php

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testDisputeDeductAtOnsetCreateSuccess' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 1000,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 1000,
                'amount_deducted'    => 1000,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],
    'testDisputeDeductAtOnsetAdjustmentTransactionCreate' => [
        'request' => [
            'url' => '/adjustments/transaction_create',
            'method' => 'POST',
            'content' => [
                'id'        =>  'LOkELUdz5YqBn2',
                'transaction_id' =>  'LN5BWCGvLdPu7T',
            ]
        ],
        'response' => [
            'content' => [
                'entity'         => 'adjustment',
                'amount'         => -1000,
                'currency'       => 'INR',
                'description'    =>  'add negative adjustment',
                'transaction_id' => 'LN5BWCGvLdPu7T'
            ],
        ]
    ],

    'testDisputeDeductAtOnsetCreateFailureWithLedgerNonRetryableError' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 1000,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
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

    'testDisputeDeductAtOnsetCreateFailureWithLedgerRetryableError' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 1000,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 1000,
                'amount_deducted'    => 1000,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeWonReversalSuccess' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'won'
            ],
        ],
        'response' => [
            'content' => [
                'amount_deducted' => 10100,
                'amount_reversed' => 10100,
            ],
        ],
    ],

    'testDisputeWonAdjustmentTransactionCreateSuccess' => [
        'request' => [
            'url' => '/adjustments/transaction_create',
            'method' => 'POST',
            'content' => [
                'id'        =>  'LOkELUdz5YqBn2',
                'transaction_id' =>  'LN5BWCGvLdPu7T',
            ]
        ],
        'response' => [
            'content' => [
                'entity'         => 'adjustment',
                'amount'         => 10100,
                'currency'       => 'INR',
                'description'    => 'Credit to reverse a previous dispute debit',
                'transaction_id' => 'LN5BWCGvLdPu7T'
            ],
        ]
    ],

    'testDisputeWonAdjustmentTransactionCreateFailureWithInvalidRequest' => [
        'request' => [
            'url' => '/adjustments/transaction_create',
            'method' => 'POST',
            'content' => [
                'id'        =>  'LOkELUdz5YqBn2',
            ]
        ],
        'response' => [
            "error_response" => [
                "code" => "BAD_REQUEST_VALIDATION_FAILURE",
                "description" => 'Both id and transaction_id are required.',
                "source" => "NA",
                "step" => "NA",
                "reason" => "NA",
                "metadata" => []
            ],
            "http_status_code" => 400,
            "internal_error_code" => "BAD_REQUEST_VALIDATION_FAILURE"
        ]
    ],

    'testDisputeWonReversalSuccessWithLedgerRetryableError' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'won'
            ],
        ],
        'response' => [
            'content' => [
                'amount_deducted' => 10100,
                'amount_reversed' => 10100,
            ],
        ],
    ],

    'testDisputeWonReversalFailureWithLedgerNonRetryableError' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'won'
            ],
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
];

