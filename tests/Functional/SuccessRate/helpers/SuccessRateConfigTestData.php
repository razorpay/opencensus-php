<?php
return [
    'testSuccessRateCreateConfig' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cutoffs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [

            ],
            'status_code'   => 400,
        ],
    ],
];
