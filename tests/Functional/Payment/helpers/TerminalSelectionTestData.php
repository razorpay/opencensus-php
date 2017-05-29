<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSubMerchantAssignWithMultipleAssignments' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL,
        ],
    ],

    'ebsDowntimeData' => [
        'gateway'     => 'ebs',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'method'      => 'netbanking',
        'comment'     => 'Test Reason',
        'source'      => 'statuscake',
        'issuer'      => 'ALL'
    ],

    'allNetbankingDowntimeData' => [
        'gateway'     => 'ALL',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'method'      => 'netbanking',
        'issuer'      => 'ALL',
        'source'      => 'other'
    ],

    'kkbkDowntimeData' => [
        'gateway'     => 'netbanking_kotak',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'method'      => 'netbanking',
        'issuer'      => 'KKBK',
        'source'      => 'other'
    ],
];
