<?php

return [
    'testPutGatewayDowntimeRedisConf' => [
        'request' => [
            'content' => [
                "config:downtime:detection:configuration_v2" =>
                    [
                        [
                            "key" => "success_rate_issuer_HDFC_create",
                            "value" => [
                                [
                                    "30",
                                    "93",
                                    "500",
                                ],
                                [
                                    "80",
                                    "96",
                                    "500",
                                ],
                            ],
                        ],
                        [
                            "key" => "success_rate_issuer_HDFC_resolve",
                            "value" => [
                                [
                                    "93",
                                    "500",
                                ],
                            ],
                        ],
                    ]
            ],
            'method'  => 'PUT',
            'url'     => '/gateway/downtime/conf',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetGatewayDowntimeRedisConf' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/gateway/downtime/conf',
        ],
        'response' => [
            'content' => [
                "config:downtime:detection:configuration_v2" =>
                    [
                        [
                            "key" => "success_rate_issuer_HDFC_create",
                            "value" =>  [['30', '2' , '0.05'],
                                ['300', '2' , '0.05']],
                        ],
                        [
                            "key" => "success_rate_issuer_HDFC_resolve",
                            "value" =>
                                [['2' , '0.40']],
                        ],
                    ]
            ]
        ]
    ],

    'testGatewayFailureDowntimeCreate' => [
        'request' => [
            'content' => [
            ],
            'method'  => 'GET',
            'url'     => '/gateway/downtimes/detection/cron?type=success_rate&key=issuer&value=HDFC',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
];
