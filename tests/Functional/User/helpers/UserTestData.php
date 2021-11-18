<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\User\Entity as UserEntity;

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

    'testSignupSourceShowingUpInMerchantAfterRegistration' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
    ],

    'testPreSignupSourceInfoStoredAfterRegistrationForBanking' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
    ],

    'testPreSignupCampaignInfoStoredAfterRegistrationForBanking' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
    ],

    'testPreSignupCampaignInfoStoredAfterRegistrationInSmallCap' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
    ],

    'testPreSignupSourceInfoStoredForWebsiteAfterRegistrationForBanking' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
    ],

    'testPreSignupSourceInfoStoredAfterRegistrationForBankingWithExtraQuotesInCookie' => [
        'request' => [
            'url' => '/users/register',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'nial',
                'email'                 => 'nial@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ],
        'response' => [
            'content' => [
                'email' => 'nial@example.com',
            ]
        ]
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

    'testRegisterWithDuplicateEmail'  => [
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRegisterWithOtp' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'abc@rzp.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
                'HTTP_X-Send-Email-Otp' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@rzp.com',
            ],
        ],
    ],

    'testRegisterForSignUpFlowInX' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'abc@rzp.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                'x_verify_email'        => 'true'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
                'HTTP_X-Send-Email-Otp' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@rzp.com',
            ],
        ],
    ],

    'testRegisterWithOauthPayload'  => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'oauth_provider is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ]
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

    'testGetAfterStoringPreSignUpSourceInfo' => [
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
                        'activated'            => false,
                        'archived_at'          => null,
                        'suspended_at'         => null,
                        'role'                 => 'owner',
                    ],
                    [
                        'activated'            => false,
                        'archived_at'          => null,
                        'suspended_at'         => null,
                        'banking_role'         => 'owner',
                        'attributes'           => [
                            'items'     => [
                                [
                                    'type'  => 'ca_page_visited',
                                    'value' => 'true'
                                ]
                            ]
                        ]
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

    'testMobileLoginWithPassword' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
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

    'testMobileFailedLoginWrongPassword' => [
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
            'status_code' => 401,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        ],
    ],


    'testMobileFailedLogin' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testMobileFailedLoginWithPasswordMultipleAccounts' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testCaptchaBypassForDemoUserInX' => [
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

    'testCaptchaBypassForDemoUserInPg' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED,
        ],
    ],

    'testMobileOtpLogin' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMobileOtpLoginWithPasswordMultipleAccounts' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testMobileOtpLoginUserUnverified' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
        ],
    ],

    'testMobileVerifyOtp' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '0123456789',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
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

    'testMailOtpLogin' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMailVerifyOtp' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'email'          => 'a@gmail.com',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
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

    'testEmailVerificationOtpSendThresholdExceeded' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
        ]
    ],

    'testEmailLoginOtpSendThresholdExceeded' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED,
        ],
    ],

    'testLoginOtpVerificationThresholdCounter' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],

    'testLoginOtpVerificationThresholdExceeded' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
        ],
    ],

    'testVerificationOtpVerificationThresholdExceeded' => [
        'request' => [
            'url'     => '/users/login/verification-otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
        ],
    ],

    'testVerificationOtpVerificationThresholdCounter' => [
        'request' => [
            'url'     => '/users/login/verification-otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],

    'testMailSendVerificationOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMobileResendVerificationOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testMailResendVerificationOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testMailSendVerificationOtpVerifiedUser' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_VERIFIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_VERIFIED,
        ],
    ],


    'testMailSendVerificationOtpWrongPassword' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PASSWORD_INCORRECT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PASSWORD_INCORRECT,
        ],
    ],

    'testMobileSendVerificationOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMobileSendVerificationOtpVerifiedUser' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED,
        ],
    ],


    'testMobileSendVerificationOtpWrongPassword' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PASSWORD_INCORRECT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PASSWORD_INCORRECT,
        ],
    ],

    'testMobileSendVerificationOtpMultipleAccounts' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testVerificationMailVerifyOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'email'          => 'a@gmail.com',
                'captcha'        => 'faked',
            ],
        ],
        'response' => [
            'content' => [
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

    'testVerificationMobileVerifyOtp' => [
        'request' => [
            'url'     => '/users/login/verification-otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '0123456789',
                'captcha'        => 'faked',
            ],
        ],
        'response' => [
            'content' => [
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

    'testMailOtpLoginUserUnverified' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_NOT_VERIFIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED,
        ],
    ],

    'testMobileResendOtpLogin' => [
        'request' => [
            'url'     => '/users/login/otp/',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                 'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testMailResendOtpLogin' => [
        'request' => [
            'url'     => '/users/login/otp/',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testFailedLoginWithOauthPayload' => [
        'request'   => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'oauth_provider is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ]
    ],


    'testOauthLogin' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token'
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthLoginForDifferentSource' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
                'oauth_source'   => 'android',
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthLoginForSourceAsXAndroid' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
                'oauth_source'   => 'x_android',
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthLoginForSourceAsXIos' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
                'oauth_source'   => 'x_ios',
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthLoginWithMissingIdToken' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id token field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testOauthLoginWithInvalidIdToken' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'invalid id token'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID_TOKEN,
        ],
    ],

    'testOauthLoginFailInvalidProvider' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"facebook\"]",
                'id_token'       => 'valid id token',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_OAUTH_PROVIDER_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_OAUTH_PROVIDER_INVALID,
        ],
    ],

    'testOauthLoginFailPasswordOauthNotPresent' => [
        'request'   => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email' => 'hello123@gmail.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOauthLoginSuccessPasswordAndOauthBothPresent' => [
        'request'   => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'password'       => 'hello123',
                'id_token'       => 'valid id token',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'password is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ]
    ],

    'testOauthLoginInvalidatePassword' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthLoginSessionNotInvalidate' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'invalidate_sessions'     => false,
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

    'testOauthLoginSessionInvalidate' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'invalidate_sessions'     => true,
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

    'testMultipleOauthProviderLogin' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
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
                        'role'         => 'owner'
                    ]
                ]
            ],
        ],
    ],

    'testOauthCreateWithUserRegisterPayload' => [
        'request'   => [
            'url'     => '/users/oauth-register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'password'       => 'hello123',
                'id_token'       => 'valid id token',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'password is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ]
    ],

    'testOauthCreate' => [
        'request'  => [
            'url'     => '/users/oauth-register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'hello123@gmail.com',
            ],
        ],
    ],

    'testOauthCreateWithInvalidIdToken' => [
        'request'  => [
            'url'     => '/users/oauth-register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'invalid id token',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID_TOKEN,
        ],
    ],

    'testOauthCreateWithMissingIdToken' => [
        'request'  => [
            'url'     => '/users/oauth-register',
            'method'  => 'POST',
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id token field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUserAccessWithProductPrimary'    => [
        'response'      => [
            'content'   => [
                'access'    => true,
                'merchant'  => [
                    'banking_role'        => null,
                    'role'                => 'owner',
                ],
            ],
        ],
    ],

    'testUserAccessWithProductBanking'   => [
        'response'      => [
            'content'   => [
                'access'    => true,
                'merchant'  => [
                    'banking_role'        => 'owner',
                    'role'                => null,
                ],
            ],
        ],
    ],

    'testUserAccessWithMappingForMultipleProducts'  => [
        'response'      => [
            'content'   => [
                'access'    => true,
                'merchant'  => [
                    'banking_role'        => 'admin',
                    'role'                => 'owner',
                ],
            ],
        ],
    ],

    'testFailedUserAccessAccrossProducts'  => [
        'response'      => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testFailedUserAccess'    => [
        'response'      => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testUserAccessWithoutMerchantIdInRequest' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
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

    'testUserEnable2FaAsCriticalAction'     => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'PATCH',
            'content'   => [
                UserEntity::SECOND_FACTOR_AUTH      => true,
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-2FA-Verified'    => 'true',
            ],
        ],

        'response'  => [
            'content'       => [
                UserEntity::SECOND_FACTOR_AUTH      => true,
            ],
        ],
    ],

    'testUserDisable2FaAsCriticalAction'     => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'PATCH',
            'content'   => [
                UserEntity::SECOND_FACTOR_AUTH      => 0,
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-2FA-Verified'    => 'true',
            ],
        ],

        'response'  => [
            'content'       => [
                UserEntity::SECOND_FACTOR_AUTH      => false,
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

    'testFailedUserEnable2faOrg2faEnforced' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ORG_2FA_ENFORCED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORG_2FA_ENFORCED,
        ],
    ],

    'testFailedUserEnable2faOrg2faNotEnforced' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testFailedUserEnable2faOneOfMultipleOrgs2faEnforced' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ORG_2FA_ENFORCED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORG_2FA_ENFORCED,
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

    'testLoginWithAccountLockedAndWith2Fa'  => [
        'request'   => [
            'url'       => '/users/login',
            'method'    =>  'post',
        ],

        'response'  => [
            'content'   => [
                'error'     => [
                    'code'          => ErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_LOCKED_USER_LOGIN,
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
        ],
    ],

    'testLoginWithAccountLockedAndWithout2Fa' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'   => [
                'error'     => [
                    'code'          => ErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_LOCKED_USER_LOGIN,
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
        ],
    ],

    'testLoginWithIncorrectPasswordCount' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT,
        ],
    ],

    'testMobileLoginWithIncorrectPasswordCountCaptchaDisabled' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT,
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

    'testTriggerTwoFaOtpWithTwoFaSetup'   => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'POST',
        ],

        'response'  => [
            'content'   => [],
        ],
    ],

    'testTriggerTwoFaOtpWithoutContactMobile'   => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'POST',
        ],

        'response'  => [
            'content'       => [
                'error'     => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                    '_internal'     => [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                    ],
                ],
            ],
            'status_code'   => 400,
        ],

        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
        ],
    ],

    'testTriggerTwoFaOtpWithoutContactMobileVerified'   => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'POST',
        ],

        'response'  => [
            'content'       => [
                'error'     => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                    '_internal'     => [
                        'internal_error_code'   => ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
                    ],
                ],
            ],
            'status_code'   => 400,
        ],

        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
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

    'testBulkUpdateUserRoleMapping' => [
        'request' => [
            'url'    => '/users/roles-mapping/bulk',
            'method' => 'PUT',
            'content' => [],
            'server'     => [
                'HTTP_X-Request-Origin'         => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [],
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

    'testResendOtpVerificationMail' => [
        'request' => [
            'url'     => '/users/resend-verification-otp',
            'method'  => 'post',
            'content' => [
                'token'=>'BUIj3m2Nx2VvVj'
            ],
        ],
        'response' => [
            'content' => [
                "token" => 'BUIj3m2Nx2VvVj',
            ],
        ],
    ],

    'testResendOtpVerificationMailForSignupFlowInX' => [
        'request' => [
            'url'     => '/users/resend-verification-otp',
            'method'  => 'post',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
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

    'testPasswordResetMailForBadEmail' => [
        'request' => [
            'url'     => '/users/reset-password',
            'method'  => 'post',
            'content' => [
                'email' => 'abc@abc.com',
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

    'testSendOtpForViewOnlyRoleInX' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ]
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testSendXMobileAppDownloadLink' => [
        'request' => [
            'url'     => '/users/mobile_app_link',
            'method'  => 'POST',
            'content' => [
                'contact_number' => '123456789',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testSendOtpForXSignupV2' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'action'         => 'x_verify_email',
                'medium'         => 'email'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ]
        ],
        'response' => [
            'content' => [
                // 'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testSendOtpWithContact' => [
        'request' => [
            'url'     => '/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'            => 'sms',
                'action'            => 'bureau_verify',
                'contact_mobile'    => '9876543210',
            ],
        ],
        'response' => [
            'content' => [
                // 'token' => 'BUIj3m2Nx2VvVj'
            ],
        ],
    ],

    'testVerifyOtpWithToken' => [
        'request' => [
            'url'     => '/users/verify_otp',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'action'         => 'verify_support_contact',
                'contact_mobile' => '9876543210'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
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

    'testSendOtpWithReplaceKeyAction' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'action' => 'replace_key'
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

    'testVerifyEmailWithOtp' => [
        'request'  => [
            'url'     => '/users/verify_email',
            'method'  => 'POST',
            'content' => [
                'otp'   => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
            ],
        ],
        'response' => [
            'content' => [
                'user' => [
                    'id'        => 'MerchantUser01',
                    'email'     => 'abc@rzp.com',
                    'confirmed' => true,
                ]
            ],
        ],
    ],

    'testVerifyEmailWithOtpInX' => [
        'request'  => [
            'url'     => '/users/verify_email',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'BUIj3m2Nx2VvVj'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'user' => [
                    'id'        => 'MerchantUser01',
                    'email'     => 'abc@rzp.com',
                    'confirmed' => true,
                ]
            ],
        ],
    ],

    'testVerifyEmailWithInvalidOtp' => [
        'request'   => [
            'url'     => '/users/verify_email',
            'method'  => 'POST',
            'content' => [
                'otp'   => '1234',
                'token' => 'BUIj3m2Nx2VvVj',
            ],
        ],
        'response'  => [
            'content'     => [
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

    'testVerifyEmailWithOtpAlreadyVerified' => [
        'request'  => [
            'url'     => '/users/verify_email',
            'method'  => 'POST',
            'content' => [
                'otp'   => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
            ],
        ],
        'response' => [
            'content' => [
                'user' => [
                    'id'        => 'MerchantUser01',
                    'email'     => 'abc@rzp.com',
                    'confirmed' => true,
                ]
            ],
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
                'contact_mobile' => '8877666666',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response'  => [
            'content'     => [
                'contact_mobile'            => '8877666666',
                'contact_mobile_verified'   => false,
            ],
        ],
    ],

    'testEditContactMobileByUserRestrictedForManagerRole' => [
        'request'   => [
            'url'     => '/users/contact/update',
            'method'  => 'patch',
            'content' => [
                'contact_mobile' => '8877666666',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
                'HTTP_X-Request-Origin'         => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testEditContactMobileByUserAndVerify' => [
        'request'  => [
            'url'     => '/users/2fa/verify',
            'method'  => 'post',
            'content' => [
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

    'testVerifyOtpValidationFailure' => [
        'request'   => [
            'url'     => '/users/2fa/verify',
            'method'  => 'post',
            'content' => [
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The otp field is required.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditContactMobileWhichIsVerifiedByUser' => [
        'request'  => [
            'url'     => '/users/contact/update',
            'method'  => 'PATCH',
            'content' => [
                'contact_mobile' => '8877666666',
            ],
        ],
        'response' => [
            'content'       => [
                'contact_mobile'            => '8877666666',
                'contact_mobile_verified'   => false,
            ],
        ],
    ],

    'testGetForUsersWithBusinessBankingEnabled' => [
        'request'  => [
            'url'     => '/users/30000000000000',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X_DASHBOARD_USER_ID' => '30000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'            => true,
                        'banking_activated_at' => 1563692021,
                    ],
                ],
                'invitations' => [
                ],
                'settings'    => [
                ],
            ],
        ],
    ],

    'testGetForUsersWithBusinessBankingEnabledForRblCA' => [
        'request'  => [
            'url'     => '/users/30000000000000',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X_DASHBOARD_USER_ID' => '30000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'            => true,
                        'ca_activation_status' => 'activated',
                    ],
                ],
                'invitations' => [
                ],
                'settings'    => [
                ],
            ],
        ],
    ],

    'testGetForUsersWithBankingAccountForIciciCA' => [
        'request'  => [
            'url'     => '/users/30000000000000',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X_DASHBOARD_USER_ID' => '30000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants' => [
                    [
                        'activated'            => true,
                        'ca_activation_status' => 'activated',
                        'accounts'             => [
                            [
                                'channel'         => 'icici',
                                'status'          => 'activated',
                                'account_number'  => '2224440041626905',
                                'account_type'    => 'current',
                                'balance'         => [
                                    'balance' => 1000000,
                                ],
                                'banking_balance' => [
                                    'account_number' => '2224440041626905',
                                    'balance'        => 1000000,
                                    'type'           => 'banking',
                                    'channel'        => 'icici',
                                ]
                            ]
                        ],
                    ],
                ],
                'invitations' => [
                ],
                'settings'    => [
                ],
            ],
        ],
    ],

    'testGetBankingUserWithPermissions'   => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'owner',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],

    'testGetBankingUserWithPermissionsNull'   => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'random_role',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],

    'testVerifyUserThroughEmail' => [
        'request'  => [
            'url'     => '/users/verify/mode/email',
            'method'  => 'post',
            'content' => [
                'otp'            => '0007',
                'token'          => 'RandomToken123',
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

    'testEditContactMobileByUserOnBankingWithoutAuthToken' => [
        'request'   => [
            'url'     => '/users/contact/update',
            'method'  => 'patch',
            'content' => [
                'contact_mobile' => '8877666666',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
                'HTTP_X-Request-Origin'    => '',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp auth token field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testEditContactMobileWhichIsVerifiedByUserOnBanking'       => [
        'request'       => [
            'url'           => '/users/contact/update',
            'method'        => 'PATCH',
            'content'       => [
                'contact_mobile'        => '9987654321',
            ],
            'server'        => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
            ],
        ],

        'response'      => [
            'content'       => [
                'contact_mobile'            => '9987654321',
                'contact_mobile_verified'   => false,
            ],
        ],
    ],

    'testEditContactMobileByUserOnBankingWithOauthToken'        => [
        'request'       => [
            'url'           => '/users/contact/update',
            'method'        => 'PATCH',
            'content'       => [
                'contact_mobile'        => '9876543219',
            ],
            'server'        => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
            ],
        ],

        'response'      => [
            'content'       => [
                'contact_mobile'            => '9876543219',
                'contact_mobile_verified'   => false,
            ],
        ],
    ],

    'testEditContactMobileByUserAndVerifyForBanking' => [
        'request'  => [
            'url'     => '/users/2fa/verify',
            'method'  => 'post',
            'content' => [
                'otp'            => '0007',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
                'HTTP_X-Request-Origin'    => 'http://x.razorpay.in',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendOtpViaEMail' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'          => 'email',
                'action'          => 'create_payout',
                'amount'          => 10000,
                'account_number'  => '1234567890',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSendBulkPayoutOtpViaEMail' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'          => 'email',
                'action'          => 'create_payout_batch',
                'total_payout_amount'          => 10000,
                'account_number'  => '1234567890',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSendBulkPayoutLinksOtpViaEMail' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'          => 'email',
                'action'          => 'create_bulk_payout_link',
                'total_payout_link_amount'          => 10000,
                'account_number'  => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testOptOutForWhatsapp' => [
        'request' => [
            'url'     => '/users/whatsapp/opt_out',
            'method'  => 'POST',
            'content' => [
                'source'          => 'api.admin.test.sms',
            ],
        ],
        'response' => [
            'content'       => [
            ],
        ],
    ],

    'testOptInStatusForWhatsapp' => [
        'request' => [
            'url'     => '/users/whatsapp/opt_in_status',
            'method'  => 'GET',
            'content' => [
                'source'          => 'api.admin.test.sms',
            ],
        ],
        'response' => [
            'content'       => [
                'consent_status' => false,
                'phone_number'   => '9999999999',
            ],
        ],
    ],

    'optInStatusForWhatsappStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.whatsapp.v1.WhatsappAPI/GetUserConsent',
            'payload' => [
                'phone_number' => '9999999999',
                'source'       => 'api.admin.test.sms',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'consent_status' => false,
                'phone_number'   => '9999999999',
            ],
        ],
    ],

    'testGetUserAndCheckEnabledMethods'   => [
        'response'      => [
                'content'     => [],
        ],
    ],

    'testOrg2faEnforced'   => [
        'response'      => [
            'content'     => [
            ],
        ],
    ],

    'testGetUserEntity'  => [
        'request' => [
            'url'       => '/users_entity/',
            'method'    => 'GET',
            'content'   => [
            ],
        ],
        'response' => [
            'content' => [
                // 'name'                    => 'repellat',
                // 'email'                   => 'hello123@c.com',
                'contact_mobile'          => '9876543210',
                // 'contact_mobile_verified' => false,
                // 'confirmed'               => false
            ],
        ],
    ],

    'testGetUserForAdminFromMerchantDashboardApp' => [
        'request'  => [
            'url'     => '/users-admin/',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id' => '',
            ],
        ],
    ],

    'testGetUserForAdminInProxyAuthShouldFail' => [
        'request'  => [
            'url'     => '/users-admin/',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUserAccessWithProductBankingViaFrontendGraphqlAuth'   => [
        'response'      => [
            'content'   => [
                'access'    => true,
                'merchant'  => [
                    'banking_role'        => 'owner',
                    'role'                => null,
                    'product'             => 'banking',
                ],
            ],
        ],
    ],

    'testUserAccessWithProductPrimaryViaFrontendGraphqlAuth'   => [
        'response'      => [
            'content'   => [
                'access'    => true,
                'merchant'  => [
                    'banking_role'        =>  null,
                    'role'                => 'owner',
                    'product'             => 'primary',
                ],
            ],
        ],
    ],

    'testOtpLoginVerifyWith2FA' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '0123456789',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED,
        ],
    ],

    'test2faWithPassword' => [
        'request' => [
            'url'     => '/users/login/otp/2fa',
            'method'  => 'POST',
            'content' => [
                'password'      => 'hello123'
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => '0123456789',
                'contact_mobile_verified' => true,
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

    'test2faWithPasswordIncorrectPassword' => [
        'request' => [
            'url'     => '/users/login/otp/2fa',
            'method'  => 'POST',
            'content' => [
                'password'      => 'hello1234'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD,
        ],
    ],

    'test2faWithPasswordTooManyIncorrectPassword' => [
        'request' => [
            'url'     => '/users/login/otp/2fa',
            'method'  => 'POST',
            'content' => [
                'password'      => 'hello1234'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
        ],
    ],
];
