<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return  [
    'testCreateCheckoutConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
	            'is_default' => true,
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    'method' => 'card',
                ],
            ]
        ],
    ],

    'testCreateCheckoutConfigWithDefaultFalse' => [
        'request' => [
            'content' => [
                'name'       => 'Test Config',
                'type'       => 'checkout',
                'is_default' => '0',
                'config'     => [
                    'issuer'   => 'sbi',
                    'network'  => 'visa',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'Test Config',
                'is_default' => false,
                'config'     => [
                    'issuer'   => 'sbi',
                    'network'  => 'visa',
                ],
            ]
        ],
    ],

    'testCreateCheckoutConfigWithoutConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'type'       => 'checkout',
                'is_default' => true,
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The config field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCheckoutConfigWithoutName' => [
        'request' => [
            'content' => [
                'is_default' => true,
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCheckoutConfigWithConfigNotInJsonFormat' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'type'       => 'checkout',
                'is_default' => true,
                'config'     => 'Wrong',
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The config must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUpdateDefaultFieldForCheckoutConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'is_default'=> '0',
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'is_default' => false,
            ]
        ],
    ],

    'testUpdateDefaultFieldForCheckoutConfigWithExistingDefaultConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'is_default'=> true,
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'is_default' => true,
            ]
        ],
    ],

    'testUpdateConfigFieldForCheckoutConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'config'     => [
                    'issuer' => 'sbi',
                ],
                'is_default' => true,
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'config is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],
];
