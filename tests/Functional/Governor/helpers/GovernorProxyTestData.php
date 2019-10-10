<?php
return [
    'testCreateNamespace' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/clients',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    "id" => "cps",
                    "name" => "cps",
                ],
                [
                    "id" => "routingengine",
                    "name" => "routingengine"
                ]

            ],
            'status_code'   => 200,
        ],
    ],
];
