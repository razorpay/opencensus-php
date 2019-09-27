<?php

return [
    'testPutGatewayDowntimeRedisConf' => [
        'request' => [
            'content' => [
                "config:downtime:detection:configuration" =>
                    [
                        [
                            "key" => "upi_mindgate",
                            "value" => [
                                [
                                    "30",
                                    "93",
                                    "500",
                                    "120",
                                ],
                                [
                                    "80",
                                    "96",
                                    "500",
                                    "320",
                                ],
                                [
                                    "160",
                                    "98",
                                    "500",
                                    "600",
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

    'upiMindGateDowntimeResponse' => [
        "key" => "upi_mindgate",
        "value" => [
            [
                "30",
                "93",
                "500",
                "120",
            ],
            [
                "80",
                "96",
                "500",
                "320",
            ],
            [
                "160",
                "98",
                "500",
                "600",
            ],
        ],
    ],

    'testGetGatewayDowntimeRedisConf' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/gateway/downtime/conf',
        ],
        'response' => [
            'content' => [
                "config:downtime:detection:configuration" =>
                    [
                        [
                            "key" => "sharp",
                            "value" =>  [
                                ['300', '50' , '2', '600'],
                                ['3000', '60' , '2', '6000']
                            ],
                        ],
                    ]
            ]
        ]
    ],

    'testStats' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/gateway/downtimes/detection/keys/stats',
        ],
        'response' => [
            'content' => [
                'stats' => [
                        [
                            'gateway' => 'sharp',
                            'stats' => [
                                    [
                                        'window_length' => '300',
                                        'threshold_percentage' => '50',
                                        'threshold_attempts' => '2',
                                        'downtime_duration' => '600',
                                        'result' => [
                                                'total_attempts' => 0,
                                                'total_failure_attempts' => 0,
                                            ],
                                    ],
                                    [
                                        'window_length' => '3000',
                                        'threshold_percentage' => '60',
                                        'threshold_attempts' => '2',
                                        'downtime_duration' => '6000',
                                        'result' => [
                                            'total_attempts' => 0,
                                            'total_failure_attempts' => 0,
                                        ],
                                    ]
                                ]
                        ]
                ],
            ],
        ]
    ],
];
