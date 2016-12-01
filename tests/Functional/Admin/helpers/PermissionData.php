<?php

return [

    'testGetPermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [],
                'count' => 2
            ],
            'status_code' => 200,
        ],
    ],
];
