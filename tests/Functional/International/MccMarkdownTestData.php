<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return  [
    'testCreateMccConfig' => [
        'request' => [
            'content' => [
                "type"  =>  "mcc_markdown",
                "name"  =>  "mcc_markdown",
                "is_default"    =>  0,
                "config"    =>  [
                    "mcc_markdown_percentage"   =>  "4"
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => []
        ],
    ],
    'testCreateMccMethodSpecificConfig' => [
        'request' => [
            'content' => [
                "type"  =>  "mcc_markdown",
                "name"  =>  "mcc_markdown",
                "is_default"    =>  0,
                "config"    =>  [
                    "mcc_markdown_percentage"   =>  "4",
                    "intl_bank_transfer_ach_mcc_markdown_percentage" => "10",
                    "intl_bank_transfer_swift_mcc_markdown_percentage" => "20",
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => []
        ],
    ],
    'testUpdateConfig' => [
        'request' => [
            'content' => [
                'type'      => 'mcc_markdown',
                "config"    =>  [
                    "mcc_markdown_percentage"   =>  "4",
                    "intl_bank_transfer_swift_mcc_markdown_percentage" => "20",
                ]
            ],
            'method'    => 'PATCH',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'is_default' => false,
            ]
        ],
    ],
    'testCreateMccConfigWithoutMandatoryFields' => [
        'request' => [
            'content' => [
                "type"  =>  "mcc_markdown",
                "name"  =>  "mcc_markdown",
                "is_default"    =>  0,
                "config"    =>  [
                    "intl_bank_transfer_swift_mcc_markdown_percentage" => "20",
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The mcc markdown percentage field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testUpdateMccConfigWithoutMandatoryFields' => [
        'request' => [
            'content' => [
                'type'      => 'mcc_markdown',
                "config"    =>  [
                    "intl_bank_transfer_swift_mcc_markdown_percentage" => "20",
                ]
            ],
            'method'    => 'PATCH',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The mcc markdown percentage field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testUpdateMccConfigMandatoryFieldsButInvalidValues' => [
        'request' => [
            'content' => [
                'type'      => 'mcc_markdown',
                "config"    =>  [
                    "mcc_markdown_percentage"   =>  "-1",
                    "intl_bank_transfer_swift_mcc_markdown_percentage" => "-1",
                ]
            ],
            'method'    => 'PATCH',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
];
