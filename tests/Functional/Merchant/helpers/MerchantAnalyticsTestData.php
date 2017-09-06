<?php

return [

    'testRouteAnalytics' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'query'=> [
                    'filter'    => [
                        'terms'     => [
                                []
                        ],
                        'ranges' => [
                                [
                                    'created_at' => [
                                        'start_time' => 123,
                                        'end_time' => 345
                                    ],
                                ],
                        ],
                    ],
                ]
            ]
        ],
        'response' => [
            'content' => [],
            'status_code'   => 200,
            'success'       => true,
            'url'           => 'https://api.razorpay.com/v1/analytics/pokedex'
            ],
        ]

];
