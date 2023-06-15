<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

$sampleApiWebhookRequest = [
    'url'    => 'http://webhook.com/v1/dummy/route',
    'secret' => 'xxxxx',
    'events' => [
        'payment.authorized' => '1',
    ],
];

$sampleApiWebhookResponse = [
    'entity'           => 'webhook',
    'id'               => 'webhook0000001',
    'created_at'       => 1585711930,
    // 'updated_at'       => ,
    'context'          => [],
    // TODO: Gets fixed in next pr.
    // 'disabled_at'      => null,
    'url'              => 'http://webhook.com/v1/dummy/route',
    'secret_exists'    => true,
    'created_by'       => 'MerchantUser01',
    'created_by_email' => 'merchantuser01@razorpay.com',
    // TODO: Gets fixed in next pr.
    // 'updated_by'       => 'MerchantUser01',
    // 'updated_by_email' => 'merchantuser01@razorpay.com',
    'active'           => true,
    'events'           => [
        'payment.authorized' => true,
        'payment.captured'   => false,
        // Other key values are not asserted..
    ],
];

$sampleApiWebhookRequestForPurePlatformPartner = array_merge($sampleApiWebhookRequest, [
    'application_id' => '10000000000App',
    'events' => [
        'account.app.authorization_revoked' => '1',
    ],
]);

$sampleApiWebhookRequestForBanking = array_merge($sampleApiWebhookRequest, [
    'events' => [
        'payout.failed' => '1',
    ],
]);

$sampleApiWebhookResponseForBanking = array_merge($sampleApiWebhookResponse, [
    'events' => [
        'payout.failed' => true,
    ],
]);

$sampleApiWebhookResponseForApp = array_merge($sampleApiWebhookResponse, [
    'application_id' => '10000000000App',
]);

$sampleApiWebhookResponseForPurePlatformPartner = array_merge($sampleApiWebhookResponse, [
    'application_id' => '10000000000App',
    'events' => [
        'account.app.authorization_revoked' => '1',
    ],
]);

$sampleApiWebhookRequestForOnboarding = [
    'url'    => 'http://webhook.com/v1/dummy/route',
    'secret' => 'xxxxx',
    'owner_id'    => 'submerchantNum',
    'owner_type'  => 'merchant',
    'alert_email' => 'alertemail@gmail.com',
    'events'      => [
        'payment.authorized',
        'payment.failed',
        'payment.dispute.created'
    ]
];

$sampleApiWebhookResponseForOnboarding = [
    'entity'      => 'webhook',
    'id'          => 'webhook0000001',
    'owner_id'    => 'submerchantNum',
    'owner_type'  => 'merchant',
    'url'              => 'http://webhook.com/v1/dummy/route',
    'secret_exists'    => true,
    'active'      => true,
    'alert_email' => 'alertemail@gmail.com',
    'events'      => [
        'payment.authorized',
        'payment.failed',
        'payment.dispute.created'
    ]
];

$sampleApiWebhookRequestForOnboardingPurePlatform = [
    'url'    => 'http://webhook.com/v1/dummy/route',
    'secret' => 'xxxxx',
    'owner_id'    => '100submerchant',
    'owner_type'  => 'merchant',
    'alert_email' => 'alertemail@gmail.com',
    'events'      => [
        'payment.authorized',
        'payment.failed',
        'payment.dispute.created'
    ]
];

$sampleApiWebhookResponseForOnboardingPurePlatform = [
    'entity'      => 'webhook',
    'id'          => 'webhook0000001',
    'owner_id'    => '100submerchant',
    'owner_type'  => 'merchant',
    'url'              => 'http://webhook.com/v1/dummy/route',
    'secret_exists'    => true,
    'active'      => true,
    'alert_email' => 'alertemail@gmail.com',
    'events'      => [
        'payment.authorized',
        'payment.failed',
        'payment.dispute.created'
    ]
];


$sampleStorkWebhookRequest = [
    'service'       => 'api-test',
    'owner_id'      => '10000000000000',
    'owner_type'    => 'merchant',
    'created_by'    => 'MerchantUser01',
    // TODO: Gets fixed/removed in next pr.
    // 'context'       =>  json_decode('{}'),
    'url'           => 'http://webhook.com/v1/dummy/route',
    'secret'        => 'xxxxx',
    'subscriptions' => [
        [
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
    ],
];

$sampleStorkWebhookResponse = [
    'id'            => 'webhook0000001',
    'created_at'    => '2020-04-01T03:32:10Z',
    'service'       => 'api-test',
    'owner_id'      => '10000000000000',
    'owner_type'    => 'merchant',
    'created_by'    => 'MerchantUser01',
    'context'       =>  [],
    'disabled_at'   => '1970-01-01T00:00:00Z',
    'url'           => 'http://webhook.com/v1/dummy/route'  ,
    'secret_exists' => true,
    'subscriptions' => [
        [
            'id'         => 'EZ4ezhzqgKNjxI',
            'created_at' => '2020-04-01T03:32:10Z',
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
    ],
];

$sampleStorkWebhookRequestForApp = array_merge($sampleStorkWebhookRequest, [
    'owner_id'   => '10000000000App',
    'owner_type' => 'application',
]);

$sampleStorkWebhookResponseForApp = array_merge($sampleStorkWebhookResponse, [
    'owner_id'   => '10000000000App',
    'owner_type' => 'application',
]);

$sampleStorkWebhookRequestForBanking = array_merge($sampleStorkWebhookRequest, [
    'service' => 'rx-test',
    'subscriptions' => [
        [
            'eventmeta' => ['name' => 'payout.failed'],
        ],
    ],
]);

$sampleStorkWebhookResponseForBanking = array_merge($sampleStorkWebhookResponse, [
    'service' => 'rx-test',
    'subscriptions' => [
        [
            'id'         => 'EZ4ezhzqgKNjxI',
            'created_at' => '2020-04-01T03:32:10Z',
            'eventmeta'  => ['name' => 'payout.failed'],
        ],
    ],
]);

$sampleStorkWebhookRequestForOnboarding = [
    'service'       => 'api-live',
    'owner_id'      => 'submerchantNum',
    'owner_type'    => 'merchant',
    'alert_email'   => 'alertemail@gmail.com',
    'url'           => 'http://webhook.com/v1/dummy/route',
    'secret'        => 'xxxxx',
    'subscriptions' => [
        [
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.failed'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.dispute.created'],
        ]
    ],
];

$sampleStorkWebhookResponseForOnboarding = [
    'id'            => 'webhook0000001',
    'owner_id'      => 'submerchantNum',
    'owner_type'    => 'merchant',
    'alert_email'   => 'alertemail@gmail.com',
    'url'           => 'http://webhook.com/v1/dummy/route',
    'secret_exists' => true,
    'subscriptions' => [
        [
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.failed'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.dispute.created'],
        ]
    ],
];


$sampleStorkWebhookRequestForOnboardingPurePlatform = [
    'service'       => 'api-live',
    'owner_id'      => '100submerchant',
    'owner_type'    => 'merchant',
    'alert_email'   => 'alertemail@gmail.com',
    'url'           => 'http://webhook.com/v1/dummy/route',
    'secret'        => 'xxxxx',
    'subscriptions' => [
        [
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.failed'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.dispute.created'],
        ]
    ],
];

$sampleStorkWebhookResponseForOnboardingPurePlatform = [
    'id'            => 'webhook0000001',
    'owner_id'      => '100submerchant',
    'owner_type'    => 'merchant',
    'alert_email'   => 'alertemail@gmail.com',
    'url'           => 'http://webhook.com/v1/dummy/route',
    'secret_exists' => true,
    'subscriptions' => [
        [
            'eventmeta'  => ['name' => 'payment.authorized'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.failed'],
        ],
        [
            'eventmeta'  => ['name' => 'payment.dispute.created'],
        ]
    ],
];

return [

    'testCreateAppWebhookInvalidPartnerType' => [
        'request'   => [
            'url'     => '/oauth/applications/10000000000Appp/webhooks',
            'content' => [
                'url'    => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateAppWebhookPurePlatform' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            // This test just validates success response and skips contents(which requires mocking and covered elsewhere).
            'content' => [],
        ],
    ],

    'testCreateAppWebhookOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            // This test just validates success response and skips contents(which requires mocking and covered elsewhere).
            'content' => [],
        ],
    ],

    'testCreateAppWebhookBankWithOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateAppWebhookFullyManagedWithOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            // This test just validates success response and skips contents(which requires mocking and covered elsewhere).
            'content' => [],
        ],
    ],

    'testGetWebhookEvents' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],

    'testGetWebhookEventsFor1CC' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.pending',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],

    'testGetWebhookEventsForProductBanking' => [
        'request' => [
            'url'       => '/webhooks/events/all',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [
                     'fund_account.validation.completed',
                     'fund_account.validation.failed',
                     'transaction.created',
                     'payout.processed',
                     'payout.reversed',
                     'payout.failed',
                     'payout.queued',
                     'payout.initiated',
                     'transaction.updated',
                     'payout.updated',
                     'payout.rejected',
                     'payout.pending',
                     'payout_link.issued',
                     'payout_link.processing',
                     'payout_link.processed',
                     'payout_link.attempted',
                    'payout_link.cancelled',
            ],
        ],
    ],

    'testGetWebhookEventsForAggregatorPartner' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],

    'testGetWebhookEventsForPurePlatformPartner' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],

    'testEditWebhookByNonOwnerUser' => [
        'request' => [
            'url' => '/webhooks/webhook0000001',
            'content' => [
                'url' => 'https://example.com',
                'events' => [
                    'payment.authorized' => '0',
                ],
                'active' => '0',
            ],
            'method' => 'put',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateWebhookForPartner' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => $sampleApiWebhookRequest,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForApp,
        ],
    ],

    'testSubscribePurePlatformSpecificWebhook' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/v1/webhooks',
            'content' => $sampleApiWebhookRequestForPurePlatformPartner,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForPurePlatformPartner,
        ],
    ],

    'createWebhookForPartnerStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequestForApp,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForApp,
            ],
        ],
    ],

    'testCreateWebhookForOAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => $sampleApiWebhookRequest,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForApp,
        ],
    ],

    'createWebhookForOAuthStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequestForApp,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForApp,
            ],
        ],
    ],

    'testCreateWebhookForPartnerMerchantNoAppAccessFailure' => [
        'request' => [
            'url'     => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => [],
            'method'  => 'POST',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateWebhookForOAuthFailure' => [
        'request' => [
            'url'     => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => [],
            'method'  => 'POST',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateWebhookForBanking' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => $sampleApiWebhookRequestForBanking,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForBanking,
        ],
    ],

    'testCreateWebhookForBankingWithMFNFeatureEnabled' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => $sampleApiWebhookRequestForBanking,
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Webhooks are controlled by partner merchant and hence webhook creation is blocked'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_WEBHOOK_DETAILS_LOCKED_FOR_MFN,
        ],
    ],

    'testCreateWebhookWithPayoutCreatedEvent' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'url'    => 'http://webhook.com/v1/dummy/route',
                'secret' => 'xxxxx',
                'events' => [
                    'payout.created' => '1',
                ],
            ]
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payout.created'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateWebhookWithPayoutCreatedEvent' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'PUT',
            'content' => [
                'url'    => 'http://webhook.com/v1/dummy/route',
                'secret' => 'xxxxx',
                'events' => [
                    'payout.created' => '1',
                ],
            ],
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payout.created'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'listWebhookForBankingWhenReturnsNoWebhooksStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'offset'   => 0,
                'limit'    => 2,
                'service'  => 'rx-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [],
        ],
    ],

    'createWebhookForBankingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequestForBanking,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForBanking,
            ],
        ],
    ],

    'testCreateWebhookForBankingAlreadyExistsFailure' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => $sampleApiWebhookRequestForBanking,
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED,
        ],
    ],

    'listWebhookForBankingBeforeCreateStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'offset'   => 0,
                'limit'    => 2,
                'service'  => 'rx-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [$sampleStorkWebhookResponseForBanking],
            ],
        ],
    ],

    'testCreateWebhookForPrimary' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/v1/webhooks',
            'content' => $sampleApiWebhookRequest,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponse,
        ],
    ],

    'createWebhookForPrimaryStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequest,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponse,
            ],
        ],
    ],

    'testCreateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url'     => '/v1/webhooks',
            'content' => $sampleApiWebhookRequestForBanking,
            'method'  => 'POST',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payout.failed'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetWebhookForHosted' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => array_merge($sampleApiWebhookResponse, [
                'secret' => 'xxxxx',
            ]),
        ],
    ],

    'getWebhookWithSecretStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/GetWithSecret',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-test',
                'owner_id'   => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => array_merge($sampleStorkWebhookResponse, [
                    'secret' => 'xxxxx',
                ]),
            ],
        ],
    ],

    'testGetWebhookForBanking' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'GET',
        ],
        'response' => [
            'content'  => $sampleApiWebhookResponseForBanking,
        ],
    ],

    'getWebhookForBankingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'rx-test',
                'owner_id'   => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForBanking,
            ],
        ],
    ],

    'testGetWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => $sampleApiWebhookResponse,
        ],
    ],

    'getWebhookForPrimaryStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-test',
                'owner_id'   => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponse,
            ],
        ],
    ],

    'testListWebhookForHosted' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                // 'entity' => 'collection',
                // 'count'  => 1,
                // 'items'  => [
                    array_merge($sampleApiWebhookResponse, [
                        'secret' => 'xxxxx',
                    ]),
                // ],
            ],
        ],
    ],

    'listWebhookWithSecretStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/ListWithSecret',
            'payload' => [
                'service'  => 'api-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    array_merge($sampleStorkWebhookResponse, [
                        'secret' => 'xxxxx',
                    ]),
                ],
            ],
        ],
    ],

    'testListWebhookForBanking' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    $sampleApiWebhookResponseForBanking,
                    array_merge($sampleApiWebhookResponseForBanking, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'listWebhookForBankingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'rx-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponseForBanking,
                    array_merge($sampleStorkWebhookResponseForBanking, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'testListWebhookWithPrivateAuth' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    $sampleApiWebhookResponse,
                    array_merge($sampleApiWebhookResponse, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'listWebhookWithPrivateAuthStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'api-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponse,
                     array_merge($sampleStorkWebhookResponse, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'testListWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    $sampleApiWebhookResponse,
                    array_merge($sampleApiWebhookResponse, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'listWebhookForPrimaryStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'api-test',
                'owner_id' => '10000000000000',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponse,
                    array_merge($sampleStorkWebhookResponse, [
                        'id'  => 'webhook0000002',
                        'url' => 'http://webhook.com/v1/dummy/route/2',
                    ]),
                ],
            ],
        ],
    ],

    'testListWebhookForPartner' => [
        'request' => [
            'url'  => '/v1/webhooks?application_id=10000000000App',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    $sampleApiWebhookResponseForApp,
                ],
            ],
        ],
    ],

    'listWebhookForPartnerStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'api-test',
                'owner_id' => '10000000000App',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponseForApp,
                ],
            ],
        ],
    ],


    'testListWebhookForPartnerMerchantNotPartnerFailure' => [
        'request' => [
            'url'  => '/v1/webhooks?application_id=10000000000App',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testListWebhookForPartnerMerchantNoAppAccessFailure' => [
        'request' => [
            'url'  => '/v1/webhooks?application_id=10000000000App',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testUpdateWebhookForBanking' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'PUT',
            'content' => $sampleApiWebhookRequestForBanking,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForBanking,
        ],
    ],

    'updateWebhookForBankingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            'payload' => [
                'webhook' => array_merge(array_except($sampleStorkWebhookRequestForBanking, 'created_by'), [
                    'id'         => 'webhook0000001',
                    'updated_by' => 'MerchantUser01',
                ]),
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForBanking,
            ],
        ],
    ],

    'testUpdateWebhookForPrimary' => [
        'request' => [
            'url' => '/v1/webhooks/webhook0000001',
            'method' => 'PUT',
            'content' => $sampleApiWebhookRequest,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponse,
        ],
    ],

    'updateWebhookForPrimaryStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            'payload' => [
                'webhook' => array_merge(array_except($sampleStorkWebhookRequest, 'created_by'), [
                    'id'         => 'webhook0000001',
                    'updated_by' => 'MerchantUser01',
                ]),
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponse,
            ],
        ],
    ],

    'testUpdateWebhookForOAuth' => [
        'request' => [
            'url'  => '/v1/webhooks/webhook0000001',
            'method' => 'PUT',
            'content' => array_merge($sampleApiWebhookRequest, [
                'application_id' => '10000000000App',
            ]),
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForApp,
        ],
    ],

    'updateWebhookForOAuthStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            'payload' => [
                'webhook' => array_merge(array_except($sampleStorkWebhookRequestForApp, 'created_by'), [
                    'id'         => 'webhook0000001',
                    'updated_by' => 'MerchantUser01',
                ]),
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForApp,
            ],
        ],
    ],

    'testUpdateWebhookForOAuthMerchantNotPartnerFailure' => [
        'request' => [
            'url'  => '/v1/webhooks/primaryWebhookId',
            'method' => 'PUT',
            'content' => array_merge($sampleApiWebhookRequest, [
                'application_id' => '10000000000App',
            ]),
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testUpdateWebhookForOAuthMerchantNoAppAccessFailure' => [
        'request' => [
            'url'  => '/v1/webhooks/primaryWebhookId',
            'method' => 'PUT',
            'content' => array_merge($sampleApiWebhookRequest, [
                'application_id' => '10000000000App',
            ]),
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testUpdateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url' => '/v1/webhooks/webhook0000001',
            'content' => $sampleApiWebhookRequestForBanking,
            'method' => 'PUT',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payout.failed'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendDisableWebhookEmailForStork' => [
        'request' => [
            'url' => '/v1/webhooks-email/deactivate',
            'content' => [
                'webhook' => [
                    'id'          => 'webhook0000001',
                    'url'         => 'http://www.test.webhook.razorpay.com',
                    'owner_id'    => '10000000000000',
                    'owner_type'  => 'merchant',
                    'alert_email' => 'alert_email@dummy.razorpay.com'
                ]
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content'  => [],
            'status_code' => 200,
        ],
    ],

    'testSendDisableWebhookEmailForStorkData' => [
        'subject'     => 'Razorpay | Webhook deactivated after 24 hours from last successful delivery for Test Merchant',
        'mode'        => 'test',
        'url'         => 'http://www.test.webhook.razorpay.com',
        'alert_email' => 'alert_email@dummy.razorpay.com',
    ],

    'testProcessWebhookEventsFromCsv' => [
        'request' => [
            'url'     => '/admin/webhooks/process_events_csv',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testProcessWebhookEventsFromCsvWhenInvalidPayload' => [
        'request' => [
            'url'     => '/admin/webhooks/process_events_csv',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name: unknown.event',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetP2pWebhookEvents' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ],
        ],
    ],

    'testCreateOnboardingWebhook' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks',
            'method' => 'POST',
            'content' => $sampleApiWebhookRequestForOnboarding,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForOnboarding,
        ],
    ],

    'createWebhookForOnboardingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequestForOnboarding,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForOnboarding,
            ],
        ],
    ],

    'testGetOnboardingWebhook' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}?webhook_id={wk_id}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForOnboarding,
        ],
    ],

    'getWebhookForOnboardingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-live',
                'owner_id'   => 'submerchantNum',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForOnboarding,
            ],
        ],
    ],

    'testListOnboardingWebhook' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks?skip=0&count=25',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    $sampleApiWebhookResponseForOnboarding,
                ],
            ]
        ],
    ],

    'listWebhookForOnboardingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'api-live',
                'owner_id' => 'submerchantNum',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponseForOnboarding,
                ],
            ],
        ],
    ],

    'testUpdateOnboardingWebhook' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}',
            'method'  => 'PATCH',
            'content' => array_merge($sampleApiWebhookRequestForOnboarding, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy'])
        ],
        'response' => [
            'content' => array_merge($sampleApiWebhookResponseForOnboarding, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
        ],
    ],

    'updateWebhookForOnboardingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            'payload' => [
                'webhook' => array_merge($sampleStorkWebhookRequestForOnboarding,  ['id' => 'webhook0000001',
                                                                                    'alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => array_merge($sampleStorkWebhookResponseForOnboarding, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
            ],
        ],
    ],

    'testDeleteOnboardingWebhook' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content' => []
        ],
    ],

    'deleteWebhookForOnboardingStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Delete',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-live',
                'owner_id'   => 'submerchantNum',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
            ],
        ],
    ],

    'testInvalidOnboardingWebhookActionByPartner' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks',
            'method' => 'POST',
            'content' => array_merge($sampleApiWebhookRequestForOnboarding, ['owner_id' => 'submerchantXXX']),
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
        ],
    ],

    'testInvalidOnboardingWebhookInput' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks',
            'method' => 'POST',
            'content' => array_merge($sampleApiWebhookRequestForOnboarding, ['owner_id' => 'submerchantXXX', 'events' => []]),
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "no webhook events provided in the input",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOnboardingWebhookForPurePlatform' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks',
            'method' => 'POST',
            'content' => $sampleApiWebhookRequestForOnboardingPurePlatform,
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForOnboardingPurePlatform,
        ],
    ],

    'createWebhookForOnboardingForPurePlatformStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            'payload' => [
                'webhook' => $sampleStorkWebhookRequestForOnboardingPurePlatform,
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForOnboardingPurePlatform,
            ],
        ],
    ],


    'testGetOnboardingWebhookForPurePlatform' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}?webhook_id={wk_id}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => $sampleApiWebhookResponseForOnboardingPurePlatform,
        ],
    ],

    'getWebhookForOnboardingForPurePlatformStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-live',
                'owner_id'   => '100submerchant',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => $sampleStorkWebhookResponseForOnboardingPurePlatform,
            ],
        ],
    ],

    'testListOnboardingWebhookForPurePlatform' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks?skip=0&count=25',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    $sampleApiWebhookResponseForOnboardingPurePlatform,
                ],
            ]
        ],
    ],

    'listWebhookForOnboardingForPurePlatformStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            'payload' => [
                'service'  => 'api-live',
                'owner_id' => '100submerchant',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhooks' => [
                    $sampleStorkWebhookResponseForOnboardingPurePlatform,
                ],
            ],
        ],
    ],

    'testUpdateOnboardingWebhookForPurePlatform' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}',
            'method'  => 'PATCH',
            'content' => array_merge($sampleApiWebhookRequestForOnboardingPurePlatform, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy'])
        ],
        'response' => [
            'content' => array_merge($sampleApiWebhookResponseForOnboardingPurePlatform, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
        ],
    ],

    'updateWebhookForOnboardingForPurePlatformStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            'payload' => [
                'webhook' => array_merge($sampleStorkWebhookRequestForOnboardingPurePlatform,  ['id' => 'webhook0000001',
                    'alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
                'webhook' => array_merge($sampleStorkWebhookResponseForOnboardingPurePlatform, ['alert_email' => 'newalertemail@gmail.com', 'secret' => 'yyyyy']),
            ],
        ],
    ],

    'testDeleteOnboardingWebhookForPurePlatform' => [
        'request' => [
            'url' => '/accounts/{account_id}/webhooks/{wk_id}',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testDeleteWebhookForProductBanking' => [
        'request' => [
            'url' => '/webhooks/{wk_id}',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'deleteWebhookForOnboardingForPurePlatformStorkExpectations' => [
        'expected_request' => [
            'path'    => '/twirp/rzp.stork.webhook.v1.WebhookAPI/Delete',
            'payload' => [
                'webhook_id' => 'webhook0000001',
                'service'    => 'api-live',
                'owner_id'   => '100submerchant',
            ],
        ],
        'mocked_response' => [
            'code' => 200,
            'body' => [
            ],
        ],
    ],

    'testGetWebhookEventsWithAccountStatusEventsForPurePlatformPartner' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],
];

