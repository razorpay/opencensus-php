<?php
return [
    'testSuccessRateCreateConfig' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/create_sr_config',
            'raw' => json_encode([
                "random" => "abcd",
            ]),
            'method' => 'POST'
        ],
        'response' => [
            'content' => [

            ],
            'status_code'   => 400,
        ],
    ],
];
