<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBearerAuth' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth' => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'dev',
            ],
            'credential' => [
                'username'   => 'rzp_test_oauth_TheTestAuthKey',
                'public_key' => 'rzp_test_oauth_TheTestAuthKey',
            ],
            'roles' => [
                'oauth::scope::read_only',
            ],
        ],
    ],

    'testBearerAuthDeletedClient' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testCreateClientsForApp' => [
        'request' => [
            'url'    => '/oauth/applications/{id}/clients',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'client_details' => [
                    'dev' => [
                        'merchant_id' => '10000000000000',
                    ],
                    'prod' => [
                        'merchant_id' => '10000000000000',
                    ],
                ],
                'clients' => [
                    ['merchant_id' => '10000000000000'],
                    ['merchant_id' => '10000000000000'],
                    ['merchant_id' => '10000000000000'],
                    ['merchant_id' => '10000000000000'],
                ],
                'old_clients' => [],
            ],
        ],
    ],

    'testDeleteClient' => [
        'request' => [
            'url' => '/oauth/applications/{id}/clients/{clientId}',
            'method' => 'DELETE',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'client_details' => [
                    'dev' => [
                        'merchant_id' => '10000000000000',
                    ],
                    'prod' => [
                        'merchant_id' => '10000000000000',
                    ],
                ],
                'clients' => [
                    ['merchant_id' => '10000000000000'],
                    ['merchant_id' => '10000000000000'],
                    ['merchant_id' => '10000000000000'],
                ],
            ],
        ],
    ],

    'testBearerAuthProdClient' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth'         => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'prod',
            ],
            'credential' => [
                'username'   => 'rzp_test_oauth_TheTestAuthKey',
                'public_key' => 'rzp_test_oauth_TheTestAuthKey',
            ],
            'roles' => [
                'oauth::scope::read_only',
            ],
        ],
    ],

    'testBearerAuthDummyRouteScope' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth'         => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'dev',
            ],
            'credential' => [
                'username'   => 'rzp_test_oauth_TheTestAuthKey',
                'public_key' => 'rzp_test_oauth_TheTestAuthKey',
            ],
            'roles' => [
                'oauth::scope::dummy.read'
            ],
        ],
    ],

    'testBearerAuthDummyRouteScopeFail' => [
        'request'  => [
            'url'     => '/payments',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testDummyFeatureEnabledOnMerchantAndApp' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
    ],

    'testBearerAuthAllowAppFeaturesRouteAccess' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnMerchant' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnApp' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnAppHeadlessOtp' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnAppAndMerchant' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testFeatureDisabledOnAppAndMerchant' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
        ],
    ],

    'testBearerAuthWriteAccess' => [
        'request'  => [
            'url'     => '/webhooks',
            'method'  => 'POST',
            'content' => [
                'url'    => 'http://webhook.com/v1/dummy/route',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'webhook',
                'url' => 'http://webhook.com/v1/dummy/route',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
            ],
        ],
    ],

    'createWebhookStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => [
                    'service'       => 'api-test',
                    'owner_id'      => '10000000000000',
                    'owner_type'    => 'merchant',
                    'url'           => 'http://webhook.com/v1/dummy/route',
                    'subscriptions' => [
                        [
                            'eventmeta'  => ['name' => 'payment.authorized'],
                        ],
                    ],
                ],
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => [
                    'id'            => 'webhook0000001',
                    'created_at'    => '2020-04-01T03:32:10Z',
                    'service'       => 'api-test',
                    'owner_id'      => '10000000000000',
                    'owner_type'    => 'merchant',
                    'url'           => 'http://webhook.com/v1/dummy/route'  ,
                    'subscriptions' => [
                        [
                            'eventmeta'  => ['name' => 'payment.authorized'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testBearerAuthWriteAccessReadRoute' => [
        'request'  => [
            'url'     => '/payments/pay_10000000000000',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testBearerAuthOutsideOfScope' => [
        'request'  => [
            'url'    => '/payments/create',
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthWithTamperedToken' => [
        'request'   => [
            'url'    => '/payments/create',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthWithTamperedJWTPayload' => [
        'request'   => [
            'url'    => '/webhooks',
            'method' => 'POST',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthExpiredToken' => [
        'request'   => [
            'url'    => '/webhooks',
            'method' => 'POST',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthLiveModeInActiveMerchant' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testRestrictedAccessFeatureEnabledOnMerchantOnly' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => [
                'name'        => 'Test virtual account',
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
                'notes'       => [
                    'a' => 'b',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testRestrictedAccessFeatureEnabledOnAppOnly' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => [
                'name'        => 'Test virtual account',
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
                'notes'       => [
                    'a' => 'b',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testRestrictedAccessFeatureEnabledOnMerchantAndApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => [
                'name'        => 'Test virtual account',
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
                'notes'       => [
                    'a' => 'b',
                ],
            ],
        ],
        'response'  => [
            'content' => [
                'entity' => 'virtual_account',
                'status' => 'active',
            ],
        ],
    ],

    'testCompetitorAppAccessS2SRouteWithoutAllowS2SFeature' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
        ],
    ],

    'testCompetitorAppAccessS2SRouteWithAllowS2SFeature' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testCompetitorAppAccessNonS2SRoute' => [
        'request'  => [
            'url'     => '/payments/pay_10000000000000',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testCacheHitForBearerToken' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testSendOtpWithBearerAuth' => [
        'request'  => [
            'url'    => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
        ],
        'response' => [
            'content' => []
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth' => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'dev',
            ],
            'credential' => [
                'username'   => 'rzp_test_oauth_TheTestAuthKey',
                'public_key' => 'rzp_test_oauth_TheTestAuthKey',
            ],
            'roles' => [
                'oauth::scope::rx_read_write',
            ],
        ],
    ],

    'testBearerAuthWithPassport' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testBearerAuthWithUnusablePassport' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

];
