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

    'testRegister'  => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'hello123@c.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'hello123@c.com',
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

    'testGetForPartnerHavingConfigs' => [
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
                        'partner_type' => 'pure_platform',
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

    'testUserAccessWithProductPrimary'    => [
        'response'      => [
            'content'   => [
                'access'    => true,
            ],
        ],
    ],

    'testUserAccessWithProductBanking'   => [
        'response'      => [
            'content'   => [
                'access'    => true,
            ],
        ],
    ],

    'testFailedUserAccessAccrossProducts'  => [
        'response'      => [
            'content'   => [
                'access'    => false,
            ],
        ],
    ],

    'testFailedUserAccess'    => [
        'response'      => [
            'content'   => [
                'access'    => false,
            ],
        ],
    ],

    'testUserEnable2fa' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => true,
            ],
        ],
    ],

    'testFailedUserEnable2faIncorrectPass' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PASSWORD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PASSWORD,
        ],
    ],

    'testUserDisable2fa' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => false,
            ],
        ],
    ],

    'testFailedUserEnable2faMerchant2faEnforced' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_ENFORCED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_ENFORCED,
        ],
    ],

    'testFailedUserDisable2faMerchant2faEnforced' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_ENFORCED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_ENFORCED,
        ],
    ],

    'testFailedUserEnable2faMobNotVerified' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
        ],
    ],

    'testFailedUserEnable2faMobNotPresent' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
        ],
    ],

    'testFailedLogin2faNoOtp' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
        ],
    ],

    'testFailedLogin2faEnforcedNoOtp' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
        ],
    ],

    'testLogin2faCorrectOtp' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'contact_mobile'             => '9999999999',
                'contact_mobile_verified'    => true,
                'confirmed'                  => true,
                'second_factor_auth'         => true,
                'second_factor_auth_setup'   => true,
                'restricted'                 => false,
                'merchants'                  => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner'
                    ]
                ]
            ],
        ]
    ],

    'testFailedLogin2faIncorrectOtp' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
        ],
    ],

    'testFailedLoginAccountLocked' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LOCKED_USER_LOGIN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
        ],
    ],

    'testFailedLogin2faNotSetup' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
        ],
    ],

    'testMaxWrongOtpLocksAccount' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
        ],
    ],

    'testFailed2faSetupUser2faNotEnabled' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED,
        ],
    ],

    'testFailed2faSetupUserLocked' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED,
        ],
    ],

    'testFailed2faSetupUserRestricted' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA,
        ],
    ],

    'testFailed2faSetupUserAlreadySetup' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_ALREADY_SETUP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_ALREADY_SETUP,
        ],
    ],

    'test2faSetupMobile' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testFailed2faSetupVerifyMobileWrongOtp' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/verify-mobile',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_SETUP_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_SETUP_INCORRECT_OTP,
        ],
    ],

    'test2faSetupVerifyMobile' => [
        'request' => [
            'url'     => '/users/login/2fa_setup/verify-mobile',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [],
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
                'medium'          => 'email',
                'action'          => 'create_payout',
                'amount'          => 10000,
                'account_number'  => '1234567890',
                // Filled from test method.
                // 'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
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
                'medium'          => 'sms',
                'action'          => 'create_payout',
                'amount'          => 10000,
                'account_number'  => '1234567890',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
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

    'testSetAccountLock' => [
        'request'  => [
            'url'     => '/users-admin/account/{id}/lock',
            'method'  => 'PUT',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'account_locked' => true,
                'user_id'        => '',
            ],
        ]
    ],

    'testSetAccountUnlock' => [
        'request'  => [
            'url'     => '/users-admin/account/{id}/unlock',
            'method'  => 'PUT',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'account_locked' => false,
                'user_id'        => '',
            ],
        ]
    ],

    'testSetAccountLockByMerchant' => [
        'request'   => [
            'url'     => '/users/account/{id}/lock',
            'method'  => 'PUT',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
        ],
    ],

    'testSetAccountUnlockByMerchant' => [
        'request'  => [
            'url'     => '/users/account/{id}/unlock',
            'method'  => 'PUT',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'account_locked' => false,
                'user_id'        => '',
            ],
        ]
    ],

    'testResetMerchantUserPassword' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testResetDiffOrgMerchantUserPassword' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testResetNonLinkedMerchantUserPassword' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testResetMerchantUserPasswordNoPermission' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testResetMerchantNonLinkedUserPassword' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT,
        ],
    ],

    'testResetMerchantNonOwnerUserPassword' => [
        'request'  => [
            'url'     => '/users/MerchantUser01/password',
            'method'  => 'put',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testUpdateContactMobile' => [
        'request'  => [
            'url'     => '/users-admin/contact',
            'method'  => 'patch',
            'content' => [
                'user_id'        => '',
                'contact_mobile' => '999999999'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testEditContactMobileByUser' => [
        'request'   => [
            'url'     => '/users/contact/update',
            'method'  => 'patch',
            'content' => [
                'contact_mobile' => '8877',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_OTP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_OTP_REQUIRED,
        ],
    ],

    'testEditContactMobileByUserRestrictedForManagerRole' => [
        'request'   => [
            'url'     => '/users/contact/update',
            'method'  => 'patch',
            'content' => [
                'contact_mobile' => '8877',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
        ],
    ],

    'testEditContactMobileByUserAndVerify' => [
        'request'  => [
            'url'     => '/users/contact/update',
            'method'  => 'patch',
            'content' => [
                'contact_mobile' => '8877',
                'otp'            => '0007',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],
];
