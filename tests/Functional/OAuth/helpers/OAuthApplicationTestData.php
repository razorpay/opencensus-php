<?php

return [
    'testFetchApplications' => [
        'request'  => [
            'url'    => '/oauth/applications',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'name'        => 'Test App',
                        'merchant_id' => '10000000000000',
                        'clients'     => [
                            'dev'  => [
                                'application_id' => '10000000000App',
                            ],
                            'prod' => [
                                'application_id' => '10000000000App',
                                'redirect_url'   => ['https://www.example.com'],
                            ],
                        ],
                    ]
                ]
            ]
        ]
    ],
];
