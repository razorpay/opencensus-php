<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreate'  => [
        'request' => [
            'url'       => '/users',
            'method'    => 'POST',
            'content'   => [
                'id'                    => '100000Razorpay',
                'name'                  => 'hello123',
                'email'                 => 'hello123@c.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'contact_mobile'        => '123456789',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'name'      => 'hello123',
                'email'     => 'hello123@c.com',
                'confirmed' => false
            ],
        ],
    ],

    'testGet' => [
        'request' => [
            'url'    => '/users/id',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true,
                'merchants'      => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testLogin' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true,
                'merchants'      => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testFailedLogin' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        ],
    ],

    'testConfirmByToken' => [
        'request' => [
            'url'     => '/users/confirm_user_by_data',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testConfirmByInvalidToken' => [
        'request' => [
            'url'     => '/users/confirm_user_by_data',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_NOT_FOUND,
        ],
    ],

    'testConfirmByEmail' => [
        'request' => [
            'url'     => '/users/confirm_user_by_data',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testEdit' => [
        'request' => [
            'url'     => '/users/id',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'name'           => 'hello',
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testChangePassword' => [
        'request' => [
            'url'     => '/users/id/password',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testChangeInvalidPassword' => [
        'request' => [
            'url'     => '/users/id/password',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The password confirmation does not match.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAttachMerchant' => [
        'request' => [
            'url'    => '/users/id/attach',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testDetachMerchant' => [
        'request' => [
            'url'    => '/users/id/detach',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testUpdateMerchant' => [
        'request' => [
            'url'    => '/users/id/update',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'contact_mobile' => null,
                'confirmed'      => true
            ],
        ],
    ],

    'testGetUserByEmail' => [
        'request' => [
            'url'    => '/users/email/%s',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
