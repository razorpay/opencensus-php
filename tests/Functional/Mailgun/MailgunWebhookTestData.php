<?php

return [
    'testValidEmailTag' => [
        'request' => [
            'content' => [
                'X-Mailgun-Tag' => 'kotak_beneficiary_mail',
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => '55842a61c53b54f30202a5ee2557eef2c2997ab7408b8cdf8f08743aa5f13ee0',
                'timestamp' => 1479133857,
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
                'timestamp' => 1479133494,
                'recipient' => 'random@email.com',
                'token' => '504f13d1b14cd999ca73f3019c4b0c938733768dc1011da105',
                'signature' => '55842a61c53b54f30202a5ee2557eef2c2997ab7408b8cdf8f08743aa5f13ee0',
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
                'signature' => '55842a61c53b54f30202a5ee2557eef2c2997ab7408b8cdf8f08743aa5f13ee0',
                'timestamp' => 1479133494,
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
];
