<?php

use RZP\Constants\HashAlgo;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testValidEmailTag' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'kotak_beneficiary_mail',
                'recipient'     => 'random@email.com',
                'event'         => 'dropped',
                'sent_at'       => '10-Jul-2017 20:47:56',
            ],
            'method'  => 'POST',
            'url'     => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ],
    ],
    'testNoEmailTag' => [
        'request' => [
            'content' => [
                'recipient' => 'random@email.com',
                'event'     => 'dropped',
                'sent_at'   => '10-Jul-2017 20:47:56',
            ],
            'method'  => 'POST',
            'url'     => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 406,
            'content'     => [],
        ],
    ],
    'testEmailTagOutOfWebhookScope' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'tag_not_in_$notifyTags',
                'recipient'     => 'random@email.com',
                'event'         => 'dropped',
                'sent_at'       => '10-Jul-2017 20:47:56',
            ],
            'method'  => 'POST',
            'url'     => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 406,
            'content'     => [],
        ],
    ],
    'testEmailBounce' => [
        'request' => [
            'content' => [
                'recipient' => 'random@email.com',
                'event'     => 'bounced',
                'sent_at'   => '10-Jul-2017 20:47:56',
            ],
            'method'  => 'POST',
            'url'     => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ],
    ],
    'testInvalidSignature' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'tag_not_in_$notifyTags',
                'recipient'     => 'random@email.com',
                'event'         => 'dropped',
                'sent_at'       => '10-Jul-2017 20:47:56',
            ],
            'method'  => 'POST',
            'url'     => '/mailgun/callback/failure',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_MAILGUN_SIGNATURE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\RecoverableException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MAILGUN_SIGNATURE
        ],
    ]
];
