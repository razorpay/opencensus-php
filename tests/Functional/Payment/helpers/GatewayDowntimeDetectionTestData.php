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
];
