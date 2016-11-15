<?php

return [
    'testValidEmailTag' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'kotak_beneficiary_mail',
                'timestamp' => 1479133857,
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/admin/email_hook/failure',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ],
    ],
    'testNoEmailTag' => [
        'request' => [
            'content' => [
                'timestamp' => 1479133494,
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/admin/email_hook/failure',
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
                'timestamp' => 1479133494,
                'recipient' => 'random@email.com',
                'event' => 'dropped'
            ],
            'method' => 'POST',
            'url' => '/admin/email_hook/failure',
        ],
        'response' => [
            'status_code' => 406,
            'content' => [],
        ],
    ],
];
