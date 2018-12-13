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
                'name'                    => 'hello123',
                'email'                   => 'hello123@c.com',
                'contact_mobile'          => '123456789',
                'contact_mobile_verified' => false,
                'confirmed'               => false
            ],
        ],
    ],

    'testGet' => [
        'request' => [
            'url'    => '/users/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner',
                    ],
                ],
                'invitations'             => [
                ],
                'settings'                => [
                ],
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
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
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
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ],
            ],
            'status_code' => 401,
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
            'url'     => '/users',
            'method'  => 'PATCH',
            'content' => [
                'name'           => 'Updated Name',
                'contact_mobile' => '123456789',
            ],
        ],
        'response' => [
            'content' => [
                'name'           => 'Updated Name',
                'contact_mobile' => '123456789',
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

    'testResendVerificationMail' => [
        'request' => [
            'url'     => '/users/resend-verification',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "success" => true,
            ],
        ],
    ],

    'testPasswordResetMail' => [
        'request' => [
            'url'     => '/users/reset-password',
            'method'  => 'post',
            'content' => [
                'email' => 'resetpass@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                "success" => true,
            ],
        ],
    ],

    'testPasswordResetByToken' => [
        'request'  => [
            'url'     => '/users/reset-password-token',
            'method'  => 'post',
            'content' => [
                'password'              => '123456xx',
                'password_confirmation' => '123456xx',
            ],
        ],
        'response' => [
            'content' => [
                "success" => true,
            ],
        ],
    ],

    'testPasswordResetByExpiredToken' => [
        'request'   => [
            'url'     => '/users/reset-password-token',
            'method'  => 'post',
            'content' => [
                'password'              => '123456xx',
                'password_confirmation' => '123456xx',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
        ],
    ],

    'testPasswordResetByUsedToken' => [
        'request'   => [
            'url'     => '/users/reset-password-token',
            'method'  => 'post',
            'content' => [
                'password'              => '123456xx',
                'password_confirmation' => '123456xx',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
        ],
    ],

    'testSendOtp' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
        ],
        'response' => [
            'content' => [
                // 'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testSendOtpViaMail' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'         => 'email',
                'action'         => 'create_payout',
                'amount'         => 10000,
                'account_number' => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
                // 'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testSendOtpWithInvalidAction' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'invalid_action',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected action is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendOtpViaMailToVerifyContact' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'mail',
                'action' => 'verify_contact',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected medium is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendOtpToVerifyContactWhenAlreadyVerified' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Contact mobile is already verified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendOtpViaSmsWhenContactDoesNotExist' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Contact mobile does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendOtpViaSmsWhenContactIsNotVerified' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'create_payout',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Contact mobile is not verified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testVerifyContactWithOtp' => [
        'request' => [
            'url'     => '/users/verify_contact',
            'method'  => 'POST',
            'content' => [
                'otp'   => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
            ],
        ],
        'response'  => [
            'content' => [
                'id'                      => 'MerchantUser01',
                'contact_mobile'          => '123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testVerifyContactWithInvalidOtp' => [
        'request' => [
            'url'     => '/users/verify_contact',
            'method'  => 'POST',
            'content' => [
                'otp'   => '1234',
                'token' => 'BUIj3m2Nx2VvVj',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],
];
