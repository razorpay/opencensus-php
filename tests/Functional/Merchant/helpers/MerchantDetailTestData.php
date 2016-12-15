<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testUpdateIFSCCode' => [
        'request' => [
            'content' =>[
                "bank_branch_ifsc"=>"ICIC0000002"
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "bank_branch_ifsc" => "ICIC0000002",
                "verification" => [
                    "status" => "disabled",
                    "disabled_reason" => "required_fields",
                ],
                "can_submit" => false,
            ],
        ],
    ],

    'testUpdateIFSCCodeWithFailure' => [
        'request' => [
            'content' =>[
                "bank_branch_ifsc"=>"ICIC000000"
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDetailForLockedMerchant' => [
        'request' => [
            'content' =>[
                "bank_branch_ifsc"=>"ICIC0000001"
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Activation form has been locked for editing by admin.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED,
        ],
    ],

    'testLockMerchant' => [
        'request' => [
            'content' =>[
                "locked" => true
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "locked" => true,
                "verification" => [
                    "status" => "disabled",
                    "disabled_reason" => "required_fields",
                ],
                "can_submit" => false,
            ],
        ],
    ],

    'testLockMerchantWithInvalidParams' => [
        'request' => [
            'content' =>[
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The locked field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testUnlockMerchant' => [
        'request' => [
            'content' =>[
                "locked" => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "locked" => false,
                "verification" => [
                    "status" => "disabled",
                    "disabled_reason" => "required_fields",
                ],
                "can_submit" => false,
            ],
        ],
    ],

    'testUnlockMerchant2' => [
        'request' => [
            'content' =>[
                "locked" => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "locked" => false,
                "verification" => [
                    "status" => "disabled",
                    "disabled_reason" => "required_fields",
                ],
                "can_submit" => false,
            ],
        ],
    ],

    'testCreateMerchantDetailIfNotExist' => [
        'request' => [
            'content' =>[
                "bank_branch_ifsc"=>"ICIC0000002",
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "bank_branch_ifsc" => "ICIC0000002",
                "verification" => [
                    "status" => "disabled",
                    "disabled_reason" => "required_fields",
                ],
                "can_submit" => false,
            ],
        ],
    ],
];
