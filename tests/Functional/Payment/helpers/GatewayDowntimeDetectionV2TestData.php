<?php

return [
    'testPutGatewayDowntimeRedisConf' => [
        'request' => [
            'content' => [
                "config:{downtime}:detection:configuration_v2" =>
                    [
                        [
                            "key" => "success_rate_card_issuer_sbin_create",
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
                            "key" => "success_rate_card_issuer_sbin_resolve",
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
    'redisConfDowntimeResponse' => [
        "key" => "success_rate_card_issuer_sbin_create",
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
    'testGetGatewayDowntimeRedisConf' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/gateway/downtime/conf',
        ],
        'response' => [
            'content' => [
                "config:{downtime}:detection:configuration_v2" =>
                    [
                        [
                            "key" => "success_rate_card_issuer_hdfc_create",
                            "value" =>  [['30', '2' , '0.05'],
                                ['300', '2' , '0.05']],
                        ],
                        [
                            "key" => "success_rate_card_issuer_hdfc_resolve",
                            "value" =>
                                [['', '2' , '0.40']],
                        ],
                        [
                            "key" => "payment_interval_upi_provider_okhdfcbank_create",
                            "value" =>
                                [['60', '2' , '0.05']],
                        ],
                        [
                            "key" => "payment_interval_upi_provider_okhdfcbank_resolve",
                            "value" =>
                                [['60', '2' , '0.40']],
                        ],
                        [
                            "key" => "payment_interval_netbanking_bank_hdfc_create",
                            "value" =>
                                [['60', '2' , '0.05']],
                        ],
                        [
                            "key" => "payment_interval_netbanking_bank_hdfc_resolve",
                            "value" =>
                                [['60', '2' , '0.40']],
                        ],
                    ]
            ]
        ]
    ],

    'testGatewayFailureDowntimeCreate' => [
        'request' => [
            'content' => [
            ],
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/cron',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
];
