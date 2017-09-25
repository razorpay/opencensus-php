<?php

return [
    'testOAuthAppAuthorizedMail' => [
        'request' => [
            'url' => '/oauth/notify/app_authorized',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ]
];
