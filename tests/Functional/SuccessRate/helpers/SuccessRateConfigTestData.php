<?php
return [
    'testSuccessRateCreateConfig' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cutoffs',
//            'raw' => json_encode([
//                "random" => "abcd",
//            ]),
            'method' => 'GET'
        ],
        'response' => [
            'content' => [

            ],
            'status_code'   => 400,
        ],
    ],
];
