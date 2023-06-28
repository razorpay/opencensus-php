<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\User\Entity as UserEntity;
use RZP\Exception\BadRequestValidationFailureException;

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

    'testChangeBankingUserRole' => [
        'request' => [
            'url'       => '/users/role',
            'method'    => 'PATCH',
            'content'   => [
                'users_list' => [
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "view_only",
                        "user_id"     => "100002Razorpay",
                    ],
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "view_only",
                        "user_id"     => "100002Razorpay",
                    ],
                    [
                        "merchant_id" => "RandomMerchantWhichDoesNotExist",
                        "role"        => "view_only",
                        "user_id"     => "100002Razorpay",
                    ],
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "view_only",
                        "user_id"     => "RandomUserWhichIsNotMappedToMerchant",
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'affected_users' => [[
                    "user_id" => "100002Razorpay",
                    "merchant_id" => "10000000000",
                    "product" => "banking",
                    "old_role" => "owner",
                    "new_role" => "view_only"
                ]],
                'ignored_users' => [
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "view_only",
                        "user_id"     => "100002Razorpay",
                    ],
                    [
                        "merchant_id" => "RandomMerchantWhichDoesNotExist",
                        "role"        => "view_only",
                        "user_id"     => "100002Razorpay",
                    ],
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "view_only",
                        "user_id"     => "RandomUserWhichIsNotMappedToMerchant",
                    ]
                ],
            ],
        ],
    ],

    'testChangeBankingUserRoleRevert' => [
        'request' => [
            'url'       => '/users/role',
            'method'    => 'PATCH',
            'content'   => [
                'users_list' => [
                    [
                        "merchant_id" => "10000000000",
                        "role"        => "owner",
                        "user_id"     => "100002Razorpay",
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'affected_users' => [[
                    "user_id" => "100002Razorpay",
                    "merchant_id" => "10000000000",
                    "product" => "banking",
                    "old_role" => "view_only",
                    "new_role" => "owner"
                ]],
                'ignored_users' => [],
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

    'testProductSwitchToXForUserWithoutEmail' => [
        'request' => [
            'url' => '/merchants/product-switch',
            'method'  => 'POST',
            'content' => [],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testVerifyOTPForAddEmailInX' => [
        'request' => [
            'url' => '/users/email/update/verify',
            'method'  => 'POST',
            'content' => [
                "otp"=> '000007',
                "email"=> 'someuser@some.com'
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => []
        ],
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

    'testRegisterRegularTestPartner'  => [
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

    'testRegisterRegularTestMerchant'  => [
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
    'testRegisterDsMerchant'  => [
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
    'testRegisterOptimiserMerchant'  => [
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

    'testRegisterWithDuplicateEmailWhenLinkedAccountExistsWithoutDashboardAccess' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'hello123@c.com',
                'password'              => 'P@ssw0rd',
                'password_confirmation' => 'P@ssw0rd',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'hello123@c.com',
            ],
        ],
    ],

    'testRegisterWithDuplicateEmailFailsWhenLinkedAccountExistsWithDashboardAccess' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'hello123@c.com',
                'password'              => 'P@ssw0rd',
                'password_confirmation' => 'P@ssw0rd',
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

    'testRegisterWithoutPassword' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'abc@rzp.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The password field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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

    'testGetActorInfo' =>[
        'request' => [
            'url'    => '/actor_info_internal/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'actor_type' => 'user',
                'actor_property_key' => 'role',
                'actor_property_value' => 'owner'
            ],
        ],
    ],

    'testProductEnabledPresenceInResponseForTrueCase' => [
        'request' => [
            'url'    => '/users/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
            ],
        ],
        'response' => [
            'content' => [
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
                                    'product'     => 'banking',
                                    'group'       => 'products_enabled',
                                    'type'        => 'X',
                                    'value'       => 'true'
                                ]
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testProductEnabledPresenceInResponseForFalseCase' => [
        'request' => [
            'url'    => '/users/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
            ],
        ],
        'response' => [
            'content' => [
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
                                    'product'     => 'banking',
                                    'group'       => 'products_enabled',
                                    'type'        => 'X',
                                    'value'       => 'false'
                                ]
                            ]
                        ]
                    ],
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

    'testLoginForMobileOauth' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'x_mobile_access_token'   => 'access_token',
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

    'testLoginForMobileOauthWithError' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\ServerErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR,
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


    'testFailedLoginForMobileOAuth' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
            ]
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

    'testLoginWithCrossOrgLoginEnable' => [
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
                    ],
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                    ]
                ]
            ],
        ],
    ],

    'testLoginWithCrossOrgLoginDisable' => [
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


    'testSendUserDetailsToSalesforce' => [
        'request' => [
            'url'     => '/users/salesforce_event',
            'method'  => 'POST',
            'content' => [
                'FirstName'                  => 'test',
                'LastName'                   => 'test',
                'Email'                      => 'testcomapany@test.com',
                'Company'                    =>  'Test Company',
                'Average_Monthly_Revenue__c' =>  '5Lacs',
                'Campaign_Name__c'           => 'Project Nike'
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
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

    'testMobileOtpLoginSkipVerificationLimitOnStage' => [
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

    'testMobileOtpLoginStorkFailed' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
        ],

    ],

    'testMobileOtpLoginForX' => [
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

    'testMobileOtpLoginWithPasswordWithNoAccounts' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [],

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

    'testMobileOtpLoginMobileAndEmailUnverified' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
        ],
    ],

    'testMobileLoginWithNewSmsTemplateAndSendsViaStork' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'contact_mobile'        => '8766776666',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testMobileSignupWithNewSmsTemplateAndSendsViaStork' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'contact_mobile'        => '8766776665',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testMobileVerifyOtpForLoginWithNewSmsTemplate' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com'
            ],
            'content' => [
                'otp'            => '0007',
                'token'          => '10000000000000',
                'contact_mobile' => '+918766776666',
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

    'testMobileVerifyOtpForSignupWithNewSmsTemplate' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com'
            ],
            'content' => [
                'otp'            => '0007',
                'token'          => '10000000000000',
                'contact_mobile' => '+918766776664',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
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

    'testMobileLoginForXWithNewSmsTemplateAndSendsViaStork' => [
        'request' => [
            'url'     => '/users/login/otp',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'contact_mobile'        => '9999999999',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testMobileLoginVerifyOtp' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
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

    'testMobileLoginVerifyOtpForX' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
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

    'testVerifyCapitalReferralDuringMobileLogin' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
                'captcha'        => 'faked',
                'referral_code'  => 'teslacomikejzc',
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

    'testMobileLoginVerifyOtpNoUserMobile' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,

                ],
            ],
            'status_code' => 400,

        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ]
    ],

    'testMobileLoginVerifyOtpNoUserEmail' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'email'          => 'a@a.com',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,

                ],
            ],
            'status_code' => 400,

        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ]
    ],

    'testMobileLoginVerifyOtpAccountLocked' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OTP_LOGIN_LOCKED,

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OTP_LOGIN_LOCKED,
        ]
    ],

    'testMobileLoginVerifyOtpForMobileOAuth' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
                'captcha'        => 'faked'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'confirmed'               => true,
                'x_mobile_access_token'   => 'access_token',
                'x_mobile_refresh_token'  => 'refresh_token',
                'x_mobile_client_id'      => 'client_id',
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

    'testOauthLogout' => [
        'request' => [
            'url'     => '/users/mobile_oauth/logout',
            'method'  => 'POST',
            'content' => [
                'client_id' => 'client_id',
                'token'     => 'token'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testOauthSwitchMerchant' => [
        'request' => [
            'url'     => '/users/switch_merchant_token',
            'method'  => 'POST',
            'content' => [
                'client_id'    => 'client_id',
                'access_token' => 'token',
                'merchant_id'  => '20000000000000'
            ],
            'server'  => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'access'                    => true,
                'merchant'                  => '20000000000000',
                'x_mobile_access_token'     => 'access_token',
                'x_mobile_refresh_token'    => 'refresh_token',
                'x_mobile_client_id'        => 'client_id',
                'current_merchant_id'       => '20000000000000'
            ]
        ],
    ],

    'testRefreshTokenForMobileOAuth' => [
        'request' => [
            'url'     => '/users/mobile_oauth/refresh_token',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'x_mobile_access_token'   => 'new_access_token',
                'x_mobile_refresh_token'  => 'updated_refresh_token',
                'x_mobile_client_id'      => 'test_client_id',
            ],
        ],
    ],

    'testRefreshTokenForMobileOAuthWithInvalidClientId' => [
        'request' => [
            'url'     => '/users/mobile_oauth/refresh_token',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Incorrect Client Id sent in request'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ]
    ],

    'testRefreshTokenForMobileOAuthWithEmptyParams' => [
        'request' => [
            'url'     => '/users/mobile_oauth/refresh_token',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMobileVerifyOtpForXWithNewSmsTemplate' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'otp'            => '0007',
                'token'          => '10000000000000',
                'contact_mobile' => '+919999999999',
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

    'testMailSignupVerifyOtp' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'email'          => 'abracadabra@gmail.com',
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

    'testMailExistsSignupVerifyOtp' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'email'          => 'abracadabra@gmail.com',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,

                ],
            ],
            'status_code' => 400,

        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
        ]
    ],

    'testMobileExistsSignupVerifyOtp' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '+919866077649',
                'captcha'        => 'faked'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,

                ],
            ],
            'status_code' => 400,

        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,
        ]
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

    'testMobileSendVerificationOtpUnverifiedEmail' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED,
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

    'testMobileSendVerificationOtpForXWithNewSmsTemplateAndSendViaStork' => [
        'request' => [
            'url'     => '/users/login/verification-otp',
            'method'  => 'POST',
            'server'        => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
            ],
            'content' => [
                'contact_mobile'    => '+919999999999',
                'password'          => 'hello123'
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
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

    'testVerificationMobileVerifyOtpForXWithNewSmsTemplate' => [
        'request' => [
            'url'     => '/users/login/verification-otp/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'otp'            => '0007',
                'token'          => '10000000000000',
                'contact_mobile' => '+919999999999',
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
                'contact_mobile' => '9012345678',
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

    'testMailOtpLoginNoAccount' => [
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

    'testOauthLoginMobileSignupEmailNotConfirmed' => [
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

    'testOauthLoginEmailSignupEmailNotConfirmed' => [
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

    'testCapitalReferralWithOauthLogin' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'email'          => 'hello123@gmail.com',
                'oauth_provider' => "[\"google\"]",
                'id_token'       => 'valid id token',
                'referral_code'  => 'teslacomikejzc'
            ],
        ],
        'response' => [
            'content' => [
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

    'testOauthLoginForMobileOAuth' => [
        'request'  => [
            'url'     => '/users/oauth-login',
            'method'  => 'POST',
            'content' => [
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'x_mobile_access_token'   => 'access_token',
                'x_mobile_refresh_token'  => 'refresh_token',
                'x_mobile_client_id'      => 'client_id',
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

    'testFailedUserChange2faBankingDemoAcc' => [
        'request' => [
            'url'     => '/users/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_DISABLED_FOR_DEMO_ACC,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_DISABLED_FOR_DEMO_ACC,
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

    'testFailedLogin2faEnforcedNoOtpForMobileOAuth' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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

    'testFailedLogin2faEnforcedNoOtpForMobileOAuthWithCreateWorkflowAction' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [
                'action' => 'create_workflow_config'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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

    'testFailedLogin2faEnforcedNoOtpForMobileOAuthWithUpdateWorkflowAction' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [
                'action' => 'update_workflow_config'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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

    'testFailedLogin2faEnforcedNoOtpForMobileOAuthWithDeleteWorkflowAction' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [
                'action' => 'delete_workflow_config'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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

    'testFailedLogin2faEnforcedNoOtpForMobileOAuthWithBulkApproveAction' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [
                'action' => 'bulk_approve_payout'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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




    'testFailedLogin2faOtpLimitExceeds' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
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

    'testSSWFSmsOtpViaStork' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id'  => '20000000000000'
            ],
            'content' => [
                'action'        => 'create_workflow_config',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ]
    ],

    'testSetMobileNumberForMerchantEnabled2FAForXWithNewSmsTemplateAndSendsViaStork' => [
        'request' => [
            'url'     => '/users/2fa_setup/contact_mobile',
            'method'  => 'PATCH',
            'server'  => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id'  => '20000000000000'
            ],
            'content' => [
                'contact_mobile'        => '9999999999',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ]
    ],

    'testFailedLogin2faForXWithNewSmsTemplateAndSendsViaStork' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'email'                 => 'user@domain.com',
                'password'              => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
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

    'testCapitalReferralFlowDuringLogin' => [
        'request' => [
            'url'     => '/users/login',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'email'                 => 'user@domain.com',
                'password'              => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                'referral_code'         => 'teslacomikejzc'
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

    'testVerify2faForXWithNewSmsTemplate' => [
        'request' => [
            'url'     => '/users/2fa/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id'  => '10000000000000'
            ],
            'content' => [
                'otp'            => '0007',
            ],
        ],
        'response' => [
            'content'     => [],
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
    'testTriggerTwoFaOtpWithTwoFaSetupForMobileUsers'   => [
        'request'   => [
            'url'       => '/users/2fa',
            'method'    => 'POST',
        ],

        'response'  => [
            'content'   => [],
        ],
    ],

    'testTriggerTwoFaOtpVerificationForMobileUsers' => [
        'request'  => [
            'url'     => '/users/2fa/verify',
            'method'  => 'post',
            'content' => [
                'otp'            => '0007',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
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

    'testcheckUserHasSetPasswordAlready' => [
        'request' => [
            'url'     => '/users/set/password',
            'method'  => 'GET',
            'content' => []
        ],
        'response' =>
            [
                'content' =>
                    [
                        'set_password' => true
                    ],
            ],
    ],

    'testcheckUserHasSetPassword' => [
        'request' => [
            'url'     => '/users/set/password',
            'method'  => 'GET',
            'content' => []
        ],
        'response' =>
            [
            'content' =>
                [
                'set_password' => true
                ],
            ],
        ],

    'testCheckUserHasSetPasswordInX' => [
        'request' => [
            'url'     => '/users/set/password',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => []
        ],
        'response' =>
            [
                'content' =>
                    [
                        'set_password' => true
                    ],
            ],
    ],

    'testSetUserPassword' => [
        'request' => [
            'url'     => '/users/set/password',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPatchUserPassword' => [
        'request' => [
            'url'     => '/users/password',
            'method'  => 'PATCH',
            'content' => [],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testChangePasswordRateLimit' => [
        'request' => [
            'url'     => '/users/password',
            'method'  => 'PUT',
            'content' => [
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'old_password'          => '12345',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CHANGE_PASSWORD_THRESHOLD_EXHAUSTED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CHANGE_PASSWORD_THRESHOLD_EXHAUSTED,
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

    'testChangePasswordMatchesLastNPasswords' => [
        'request' => [
            'url'     => '/users/password',
            'method'  => 'PUT',
            'content' => [
                'password'              => 'P@ssw0rd',
                'password_confirmation' => 'P@ssw0rd',
                'old_password'          => 'P@ssw0rd',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD,
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

    'testUpdateUserRoleByOwner' => [
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

    'testUpdateUserRoleByNonOwner' => [
        'request' => [
            'url'    => '/users/id/update',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testUpdateToOwnerRole' => [
        'request' => [
            'url'    => '/users/id/update',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Action not allowed for owner role',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_OWNER_ROLE,
        ],
    ],

    'testUpdateOwnerRole' => [
        'request' => [
            'url'    => '/users/id/update',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Action not allowed for owner role',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_OWNER_ROLE,
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

    'testResendEmailOtpVerificationMailThresholdExhausted' => [
        'request' => [
            'url'     => '/users/resend-verification-otp',
            'method'  => 'post',
            'content' => [
                'token'=>'BUIj3m2Nx2VvVj'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
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

    'testPasswordResetMailCaseInsensitive' => [
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

    'testPasswordResetSMS' => [
        'request' => [
            'url'     => '/users/reset-password',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '7349196832',
            ],
        ],
        'response' => [
            'content' => [
                "success" => true,
            ],
        ],
    ],

    'testPasswordResetUnverifiedMobileNumber' => [
        'request' => [
            'url'     => '/users/reset-password',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '7349196832',
            ],
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

    'testPasswordResetMobileNumberMultipleUsersAssociated' => [
        'request' => [
            'url'     => '/users/reset-password',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '7349196832',
            ],
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

    'testPasswordResetByTokenCaseInsensitive' => [
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

    'testPasswordResetByTokenAndMobile' => [
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

    'testPasswordResetByExpiredTokenAndMobile' => [
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

    'testPasswordResetByUsedTokenAndMobile' => [
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

    'testSendOtpForScanAndPay' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'action' => 'create_composite_payout_with_otp',
                "amount" => 100000,
                "purpose" => "refund",
                "vpa" => "vivek@okhdfcbank",
                "account_number" => "3434605717969098"
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSendOtpVerifyUser' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_user',
            ],
        ],
        'response' => [
            'content' => [],
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

    'testSmsTemplateSelection' => [
    'request' => [
        'url'     => '/users/otp/send',
        'method'  => 'POST',
        'content' => [
            'medium'          => 'sms',
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

    'testSendOtpForCreatePayoutWithoutMobileNumberInReceiver' => [
        'request' => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
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

    'testBulkPayoutApproveSmsTemplateSelection' => [
        'request'  => [
            'url'     => '/users/otp/send',
            'method'  => 'POST',
            'content' => [
                'medium'                 => 'sms',
                'action'                 => 'bulk_payout_approve',
                'approved_payout_count'  => 12,
                'approved_payout_amount' => "123",
                'rejected_payout_count'  => 0,
                'rejected_payout_amount'  => "0"
            ],
        ],
        'response' => [
            'content' => [
                // 'token' => 'BUIj3m2Nx2VvVj'
            ],
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

    'testVerifyContactWithOtpWithAction' => [
        'request' => [
            'url'     => '/users/verify_contact',
            'method'  => 'POST',
            'content' => [
                'otp'   => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'verify_user',
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

    'testVerifyOtpAndUpdateContactMobile' => [
        'request' => [
            'url'     => '/users/verify/update/new/mobile',
            'method'  => 'POST',
            'content' => [
                'receiver' => '9123456789',
                'otp'      => '000007',
            ],
        ],
        'response'  => [
            'content' => [
                'id'                      => 'MerchantUser01',
                'contact_mobile'          => '9123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testVerifyOtpAndUpdateContactMobileWhenOwnerUserAssociatedWithMultipleMerchants' => [
        'request' => [
            'url'     => '/users/verify/update/new/mobile',
            'method'  => 'POST',
            'content' => [
                'receiver' => '9123456789',
                'otp'      => '000007',
            ],
        ],
        'response'  => [
            'content' => [
                'id'                      => 'MerchantUser01',
                'contact_mobile'          => '9123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testVerifyOtpAndUpdateContactMobileWhenNotOwnerRoleOfMerchant' => [
        'request' => [
            'url'     => '/users/verify/update/new/mobile',
            'method'  => 'POST',
            'content' => [
                'receiver' => '9123456789',
                'otp'      => '000007',
            ],
        ],
        'response'  => [
            'content' => [
                'contact_mobile'          => '9123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testVerifyOtpAndUpdateContactMobileWhenUserOwnerOfMerchantwithMultipleOwnerUsers' => [
        'request' => [
            'url'     => '/users/verify/update/new/mobile',
            'method'  => 'POST',
            'content' => [
                'receiver' => '9123456789',
                'otp'      => '000007',
            ],
        ],
        'response'  => [
            'content' => [
                'id'                      => 'MerchantUser01',
                'contact_mobile'          => '9123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testVerifyUpdateCacheValueForUpdateContactMobile' => [
        'request' => [
            'url'     => '/users/verify/update/new/mobile',
            'method'  => 'POST',
            'content' => [
                'receiver' => '9123456789',
                'otp'      => '000007',
            ],
        ],
        'response'  => [
            'content' => [
                'id'                      => 'MerchantUser01',
                'contact_mobile'          => '9123456789',
                'contact_mobile_verified' => true,
            ],
        ],
    ],

    'testSendOtpLimitForUpdateContactMobileExceeded' => [
        'request'   => [
            'url'     => '/users/contact/sendotp',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '9876543210',
                'otp_auth_token' => 'otp_auth_token',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SMS_OTP_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
        ],
    ],

    'testLimitForUpdateContactMobileExceeded' => [
        'request'   => [
            'url'     => '/users/contact/sendotp',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '9876543210',
                'otp_auth_token' => 'otp_auth_token',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LIMIT_FOR_UPDATE_CONTACT_MOBILE_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LIMIT_FOR_UPDATE_CONTACT_MOBILE_EXCEEDED,
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

    'testPostSendOtpForContactMobileUpdate' => [
        'request'   => [
            'url'     => '/users/contact/sendotp',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '9707753394',
                'otp_auth_token' => 'otp_auth_token',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response'  => [
            'content'     => [
            ],
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

    'test2faVerifyWrongOtp' => [
        'request'  => [
            'url'     => '/users/2fa/verify',
            'method'  => 'post',
            'content' => [
                'otp'            => '0006',
            ],

        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
        ]
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

    'testIDORBlockForGetUsersViaDashboardGuestAppAuth' => [
        'request'  => [
            'url'     => '/users/30000000000000',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'id'    =>  '20000000000000'
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

    'testGetForUsersWithBankingAccountForCAHavingGatewayBalance' => [
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
                                    'balance' => 30000000,
                                ],
                                'banking_balance' => [
                                    'account_number' => '2224440041626905',
                                    'balance'        => 30000000,
                                    'type'           => 'banking',
                                    'channel'        => 'icici',
                                    'last_fetched_at'=> 1659873429,
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

    'testGetForUsersWithBusinessBankingEnabledForRblCAWithArchivedStatus' => [
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
                    ],
                ],
                'invitations' => [
                ],
                'settings'    => [
                ],
            ],
        ],
    ],

    'testGetForUsersWithBusinessBankingEnabledWithTwoRblCAWhereOneIsArchived' => [
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

    'testGetBankingUserWithPermissionsForSubMerchant'   => [
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

    'testGetPermissionsForCARoles'  => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'chartered_accountant',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],

    'testGetBankingUserWithMerchantRulesWithPermissionNotPresent'   => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'operations',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],

    'testGetBankingUserWithMerchantRulesWithPermissionFalse'   => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'admin',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],

    'testGetBankingUserWithMerchantRules'   => [
        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'admin',
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

    'testVerifyUserThroughMode' => [
        'request'  => [
            'url'     => '/users/verify/mode/sms_and_email',
            'method'  => 'post',
            'content' => [
                'otp'            => '0007',
                'token'          => 'RandomToken123',
                'medium'         => 'sms_and_email',
                'action'         => 'second_factor_auth',
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

    'testFetchMerchantIdsForUserContact' => [
        'request' => [
            'url'     => '/internal/merchant/fetch_for_user_contact/9091929394',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'owner_ids' => ['12345678901234'],
            ],
        ],
    ],

    'testFetchPrimaryUserContact' => [
        'request' => [
            'url'     => '/internal/merchant/fetch_primary_user_contact/12345678901234',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'owner_contacts' => ['+919091929394'],
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

    'testVerifyContactMobile' => [
        'request' => [
            'url'     => '/users-admin/contact/verify',
            'method'  => 'post',
            'content' => [
                    [
                        "merchant_login_email"=>"bbsuhas-axis-2@test.com",
                        "update_contact"=>"9736605649"
                    ],
                    [
                        "merchant_login_email"=>"ajay.icici@icici.com",
                        "update_contact"=>"1234578960"
                    ]
            ],
        ],
        'response' => [
            'content' => [
                    'bbsuhas-axis-2@test.com',
                    'ajay.icici@icici.com'
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
                'contact_mobile' => '9012345678',
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

    'testOtpLoginVerifyWith2FAForMobileOauth' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '9012345678',
                'captcha'        => 'faked'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth'   => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
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

    'testOtpLoginVerifyWith2FAWithoutPassword' => [
        'request' => [
            'url'     => '/users/login/otp/verify',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'otp'            => '0007',
                'token'          => 'Gvt61zZ3Iwzcqy',
                'contact_mobile' => '73491987654454',
                'captcha'        => 'faked',
            ],
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
                'contact_mobile'          => '9012345678',
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

    'test2faWithPasswordForMobileOauth' => [
        'request' => [
            'url'     => '/users/login/otp/2fa',
            'method'  => 'POST',
            'content' => [
                'password'      => 'hello123'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => '9012345678',
                'contact_mobile_verified' => true,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner'
                    ]
                ],
                'x_mobile_access_token'  => 'access_token',
                'x_mobile_refresh_token' => 'refresh_token',
                'x_mobile_client_id'     => 'client_id',
            ],
        ],
    ],

    'test2faWithOtpForXReturnsOtpAuthToken' => [
        'request' => [
            'url'     => '/users/2fa/verify',
            'method'  => 'POST',
            'content' => [
                'otp'      => '0007'
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => '9012345678',
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

    'test2faWithOtpForXReturnsOtpAuthTokenForMobileOAuth' => [
        'request' => [
            'url'     => '/users/2fa/verify',
            'method'  => 'POST',
            'content' => [
                'otp'      => '0007'
            ],
            'server'     => [
                'HTTP_X-Mobile-Oauth' => 'true',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => '9012345678',
                'contact_mobile_verified' => true,
                'confirmed'               => true,
                'x_mobile_access_token'   => 'access_token',
                'x_mobile_refresh_token'  => 'refresh_token',
                'x_mobile_client_id'      => 'client_id',
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


    'testUserDeviceDetails' => [
        'request' => [
            'url'     => '/user/device-details',
            'method'  => 'POST',
            'content' => [
                'appsflyer_id'  => '1123456789',
                'signup_source' => 'mobile',
            ],
        ],
        'response' => [
            'content' => [
                'appsflyer_id'  => '1123456789'
            ],
        ],
    ],

    'testUserDeviceDetailsSignupSourceIosAppsflyerIdAbsent' => [
        'request' => [
            'url'     => '/user/device-details',
            'method'  => 'POST',
            'content' => [
                'appsflyer_id'  => null,
                'signup_source' => 'ios',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testUserDeviceDetailsSignupSourceAndroidAppsflyerIdAbsent' => [
        'request' => [
            'url'     => '/user/device-details',
            'method'  => 'POST',
            'content' => [
                'appsflyer_id'  => null,
                'signup_source' => 'android',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testUserDeviceDetailsSignupSourceNonMobile' => [
        'request' => [
            'url'     => '/user/device-details',
            'method'  => 'POST',
            'content' => [
                'signup_source' => 'something_else',
            ],
        ],
        'response' => [
            'content' => [
                'appsflyer_id'  => null
            ],
        ],
    ],

    'testUserRegisterSendSignupOtpViaSms' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testUserRegisterSendSignupOtpUnsupportedCountryCode' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [
                'contact_mobile' => '+2126087981231'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Unsupported Country Code.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUserRegisterSendSignupOtpViaSmsMobileExists' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS,
        ],
    ],

    'testUserRegisterSendSignupOtpViaEmailEmailExists' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
        ],
    ],

    'testUserRegisterSendSignupOtpViaEmail' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testUserRegisterVerifySignupOtpIncorrectOtp' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
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

    'testUserRegisterSendSignupOtpViaSmsLimitReached' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED,
        ],
    ],

    'testUserRegisterSendSignupOtpViaEmailLimitReached' => [
        'request' => [
            'url'     => '/users/register/otp',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED,
        ],
    ],

    'testUserRegisterVerifySignupOtpIncorrectOtpLimitOnAnOtpReached' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED,
        ],
    ],

    'testUserRegisterVerifySignupOtpTotalIncorrectOtpLimitReached' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
        ],
    ],

    'testUserRegisterVerifySignupOtpSms' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'contact_mobile'        => '8877665544',
                'captcha'               => 'faked',
                'token'                 => 'token',
                'otp'                   => '0007',
            ],
        ],
        'response' => [
            "content" => [
                "contact_mobile"            => '8877665544',
                "signup_via_email"          => 0,
                "confirmed"                 => false,
                "email_verified"            => false,
                "contact_mobile_verified"   => true,
                "email"                     => null
            ]
        ]
    ],

    'testUserRegisterVerifySignupOtpSmsEasyOnboardingSplitzOn' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'contact_mobile'        => '8877665544',
                'captcha'               => 'faked',
                'token'                 => 'token',
                'otp'                   => '0007',
                'physical_store'        => true,
                'social_media'          => true,
                'live_website_or_app'   => false,
                'others'                => "others",
                'signup_campaign'       => 'easy_onboarding'
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'            => '8877665544',
                'signup_via_email'          => 0,
                'confirmed'                 => false,
                'email_verified'            => false,
                'contact_mobile_verified'   => true,
                'email'                     => null,
                'signup_campaign'           => 'easy_onboarding'
            ]
        ]
    ],

    'testUserRegisterVerifySignupOtpSmsEasyOnboardingSplitzOff' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [
                'contact_mobile'        => '8877665544',
                'captcha'               => 'faked',
                'token'                 => 'token',
                'otp'                   => '0007',
                'physical_store'        => true,
                'social_media'          => true,
                'live_website_or_app'   => false,
                'others'                => "others",
                'signup_campaign'       => 'easy_onboarding'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ACTION,
        ],
    ],

    'testUserRegisterVerifySignupOtpEmail' => [
        'request' => [
            'url'     => '/users/register/otp/verify',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [

        ]
    ],

    'testAddEmailFromProfileSection' => [
        'request' => [
            'url' => '/users/email/update/verify',
            'method'  => 'POST',
            'content' => [
                "otp"=> '000007',
                "email"=> 'someuser@some.com'
            ],
        ],
        'response' => [
            'content' => [
                'email_verified' => true,
                'email' => 'someuser@some.com'
            ]
        ]
    ],

    'testAddEmailFromProfileSectionNotOwner' => [
        'request' => [
            'url' => '/users/email/update/verify',
            'method'  => 'POST',
            'content' => [
                "otp"=> '000007',
                "email"=> 'someuser@some.com'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testAddEmailFromProfileSectionEmailAlreadyPresent' => [
        'request' => [
            'url' => '/users/email/update/verify',
            'method'  => 'POST',
            'content' => [
                "otp"=> '000007',
                "email"=> 'someuser@some.com'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Email is already present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddEmailFromProfileSectionEmailAlreadyTaken' => [
        'request' => [
            'url' => '/users/email/update/verify',
            'method'  => 'POST',
            'content' => [
                "otp"=> '000007',
                "email"=> 'someuser@some.com'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
        ],
    ],

    'testSendOTPForAddingEmailFromProfileSection' => [
        'request' => [
            'url' => '/users/email/update',
            'method'  => 'POST',
            'content' => [
                "email"=> 'someuser@some.com'
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'someuser@some.com'
            ]
        ]
    ],

    'testUserRegisterFromVendorPortalInvitation'  => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'vendorportal@razorpay.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'login' => true,
            ],
        ],
    ],

    'testUserRegisterFoBankPocRole'  => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'random@rbl.com',
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'login' => true,
            ],
        ],
    ],

    'testUpdateContactMobileAlreadyVerifiedFailure' => [
        'request'   => [
            'url'     => '/users/contact/sendotp',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '+919876543210',
                'otp_auth_token' => 'otp_auth_token',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
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

    'testUserContactMobileAlreadyTakenFailure' => [
        'request'   => [
            'url'     => '/users/contact/sendotp',
            'method'  => 'post',
            'content' => [
                'contact_mobile' => '+919876543210',
                'otp_auth_token' => 'otp_auth_token',
            ],
            'server'  => [
                'HTTP_X-Dashboard-User-id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
        ],
    ],

    'testMerchantGetTagsRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/merchants/10000000000000/tags',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testCurrencyFetchAllProxyRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/currency/all/proxy',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testMerchantPartnerConfigsFetchProxyRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/merchants/me/partner/configs',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testSettlementHolidaysRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/settlement/holidays',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testSettlementAmountRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/settlements/amount',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testUserFetchPurposeCodeRouteViaBankingProductWithBlockingFeatureEnabled' => [
        'request' => [
            'url'     => '/users/purpose/code',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testWhatsAppOptInForX' => [
        'request' => [
            'url'     => '/users/whatsapp/opt_in',
            'method'  => 'POST',
            'content' => [
                'source'           => 'x',
                'business_account' => 'razorpayx'
            ],
        ],
        'response' => [
            'content' => [
                'optin_status' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testWhatsAppOptInStatusForX' => [
        'request' => [
            'url'     => '/users/whatsapp/opt_in_status?source=x&business_account=razorpayx',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'phone_number' => '9876543210'
            ],
            'status_code' => 200,
        ],
    ],

    'testWhatsAppOptOutForX' => [
        'request' => [
            'url'     => '/users/whatsapp/opt_out',
            'method'  => 'POST',
            'content' => [
                'source'           => 'x',
                'business_account' => 'razorpayx'
            ],
        ],
        'response' => [
            'content' => [
                'optin_status' => false
            ],
            'status_code' => 200,
        ],
    ],

    'testChangeUserName' => [
        'request' => [
            'url' => '/users/update_name',
            'method'  => 'POST',
            'content' => [
                'name' => 'Razorpay user 1'
            ],
        ],
        'response' => [
            "status_code" => 200,
            "content" => [
                'name' => 'Razorpay user 1'
            ]
        ],
    ],

    'testChangeUserNameByPassingSameName' => [
        'request' => [
            'url' => '/users/update_name',
            'method'  => 'POST',
            'content' => [
                'name' => 'Razorpay user'
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'User name must be different from existing name',
                ],
            ]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USERNAME_MUST_BE_DIFFERENT,
        ],
    ],

    'testChangeUserNameByPassingInvalidName' => [
        'request' => [
            'url' => '/users/update_name',
            'method'  => 'POST',
            'content' => [
                'name' => 'Ra'
            ],
        ],
        'response' => [
            "status_code" => 400,
            "content" => [
                "error" => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name must be at least 4 characters.',
                ],
            ]
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
