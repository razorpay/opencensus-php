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
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => hash_hmac(HashAlgo::SHA256, time() . '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105', config('applications.mailgun.key')),
                'timestamp' => time(),
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ],
    ],
    'testNoEmailTag' => [
        'request' => [
            'content' => [
                'timestamp' => time(),
                'recipient' => 'random@email.com',
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => hash_hmac(HashAlgo::SHA256, time() . '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105', config('applications.mailgun.key')),
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 406,
            'content' => [],
        ],
    ],
    'testEmailTagOutOfWebhookScope' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'tag_not_in_$notifyTags',
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => hash_hmac(HashAlgo::SHA256, time() . '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105', config('applications.mailgun.key')),
                'timestamp' => time(),
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/mailgun/callback/failure',
        ],
        'response' => [
            'status_code' => 406,
            'content' => [],
        ],
    ],
    'testInvalidSignature' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'tag_not_in_$notifyTags',
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => hash_hmac(HashAlgo::SHA256, time() . '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105', 'random_mailgun_key'),
                'timestamp' => time(),
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/mailgun/callback/failure',
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
