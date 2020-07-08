<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testCreateWebhookForOauth' => [
        'request' => [
            'url'  => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => [],
            'method' => 'POST',
        ],
        'response' => []
    ],

    'testCreateWebhookForOauthFailure' => [
        'request' => [
            'url'  => '/v1/oauth/applications/10000000000App/webhooks',
            'content' => [],
            'method' => 'POST',
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
            'url'  => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [],
            'method' => 'POST',
        ],
        'response' => []
    ],

    'testCreateWebhookForBankingAlreadyExistsFailure' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [],
            'method' => 'POST',
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

    'testCreateWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'content' => [],
            'method' => 'POST',
        ],
        'response' => []
    ],

    'testCreateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url' => '/v1/webhooks',
            'content' => [],
            'method' => 'POST',
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

    'testGetWebhookForHosted' => [
        'request' => [
            'url'  => '/v1/webhooks/primaryWebhookId',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testGetWebhookForBanking' => [
        'request' => [
            'url'  => '/v1/webhooks/bankingWebhookId',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testGetWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks/primaryWebhookId',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testListWebhookForHosted' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
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
            'content'  => [],
        ],
    ],

    'testListWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testUpdateWebhookForBanking' => [
        'request' => [
            'url'  => '/v1/webhooks/bankingWebhookId',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'PUT',
        ],
        'response' => [],
    ],

    'testUpdateWebhookForBankingNotExistsFailure' => [
        'request' => [
            'url'  => '/v1/webhooks/bankingWebhookId',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'PUT',
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_STORK_WEBHOOK_NOT_FOUND,
        ],
    ],

    'testUpdateWebhookForPrimary' => [
        'request' => [
            'url'  => '/v1/webhooks/primaryWebhookId',
            'method' => 'PUT',
        ],
        'response' => [],
    ],

    'testUpdateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url' => '/v1/webhooks/primaryWebhookId',
            'content' => [],
            'method' => 'PUT',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payout.processed'
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
];
