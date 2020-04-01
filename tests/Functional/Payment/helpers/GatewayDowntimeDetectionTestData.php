<?php

return [
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
