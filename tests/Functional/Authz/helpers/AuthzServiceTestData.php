<?php


return [
    'testFetchActionList' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/authz/fetch_actions',
            'server' => [],
            'content' => [
                "action_name_prefix"   => "GET"
            ]
        ],
        'response' => [
            'content' => [
                "pagination_token" => null,
                "count" => "1",
                "items" => [
                    [
                        "id" => "FuEmr64NaJWKG7",
                        "name" => "get",
                        "type" => "ACTION_TYPE_R"
                    ]
                ]

            ]
        ],
    ],
];
