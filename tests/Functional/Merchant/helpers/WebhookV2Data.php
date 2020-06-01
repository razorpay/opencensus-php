<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testCreateWebhookForOauth' => [
        'request' => [
            'url'  => '/v2/oauth/applications/10000000000App/webhooks',
            'content' => [],
            'method' => 'POST',
        ],
        'response' => []
    ],

    'testCreateWebhookForOauthFailure' => [
        'request' => [
            'url'  => '/v2/oauth/applications/10000000000App/webhooks',
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
            'url'  => '/v2/webhooks',
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
            'url'  => '/v2/webhooks',
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
            'url'  => '/v2/webhooks',
            'content' => [],
            'method' => 'POST',
        ],
        'response' => []
    ],

    'testCreateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url' => '/v2/webhooks',
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
            'url'  => '/v2/webhooks/primaryWebhookId',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testGetWebhookForBanking' => [
        'request' => [
            'url'  => '/v2/webhooks/bankingWebhookId',
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
            'url'  => '/v2/webhooks/primaryWebhookId',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testListWebhookForHosted' => [
        'request' => [
            'url'  => '/v2/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testListWebhookForBanking' => [
        'request' => [
            'url'  => '/v2/webhooks',
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
            'url'  => '/v2/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testUpdateWebhookForBanking' => [
        'request' => [
            'url'  => '/v2/webhooks/bankingWebhookId',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'method' => 'PUT',
        ],
        'response' => [],
    ],

    'testUpdateWebhookForBankingNotExistsFailure' => [
        'request' => [
            'url'  => '/v2/webhooks/bankingWebhookId',
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
            'url'  => '/v2/webhooks/primaryWebhookId',
            'method' => 'PUT',
        ],
        'response' => [],
    ],

    'testUpdateWebhookInvalidProductEventFailure' => [
        'request' => [
            'url' => '/v2/webhooks/primaryWebhookId',
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
];
