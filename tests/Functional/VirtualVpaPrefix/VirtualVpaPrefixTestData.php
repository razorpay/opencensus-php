<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;

return [
    'testValidatePrefix' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => 'paytorzp',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => true,
            ],
        ],
    ],

    'testValidatePrefixNumeric' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => '12345',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => true,
            ],
        ],
    ],

    'testValidatePrefixInvalidLength' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => 'paytorazorpay',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => false,
            ],
        ],
    ],

    'testValidatePrefixNotAlphanumeric' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => 'pay_rzp',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => false,
            ],
        ],
    ],

    'testValidatePrefixDefault' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => 'payto00000',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => false,
            ],
        ],
    ],

    'testValidatePrefixAlreadyExists' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes/validate',
            'method'    => 'get',
            'content'   => [
                'prefix' => 'paytorzp',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid' => false,
            ],
        ],
    ],

    'testCreatePrefix' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'paytorzp',
            ],
        ],
        'response' => [
            'content' => [
                'prefix' => 'paytorzp',
            ],
        ],
    ],

    'testCreatePrefixNumeric' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => '12345',
            ],
        ],
        'response' => [
            'content' => [
                'prefix' => '12345',
            ],
        ],
    ],

    'testCreatePrefixInvalidLength' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'paytorazorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'The prefix may not be greater than 10 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePrefixNotAlphanumeric' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'pay_rzp',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'The prefix may only contain letters and numbers.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePrefixDefault' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'payto00000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VIRTUAL_VPA_PREFIX_UNAVAILABLE
        ],
    ],

    'testCreatePrefixAlreadyExists' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'paytorzp',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VIRTUAL_VPA_PREFIX_UNAVAILABLE
        ],
    ],

    'testUpdatePrefix' => [
        'request' => [
            'url'       => '/virtual_vpa_prefixes',
            'method'    => 'post',
            'content'   => [
                'prefix' => 'acmecorp',
            ],
        ],
        'response' => [
            'content' => [
                'prefix' => 'acmecorp',
            ],
        ],
    ],
];
