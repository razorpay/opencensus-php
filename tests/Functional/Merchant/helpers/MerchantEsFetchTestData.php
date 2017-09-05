<?php

return [
    'testGetMerchantsFromEsByQ' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'q' => 'jitendra',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
