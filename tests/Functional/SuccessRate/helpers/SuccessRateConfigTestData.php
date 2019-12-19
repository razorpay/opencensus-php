<?php
return [
    'testSuccessRateGetConfig' => [
        'request' => [
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ],
            'url' => '/cutoffs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    "method" => "card",
                    "network" => "mastercard",
                    "high" => "10",
                    "medium" => "30",
                ],
                [
                    "method" => "upi",
                    "psp" => "paytm",
                    "high" => "5",
                    "medium" => "20",
                ]
            ],
            'status_code'   => 200,
        ],
    ],
//    'testSuccessRateUpdateConfig' => [
//        'request' => [
//            'server' => [
//                'CONTENT_TYPE'  => 'application/json',
//            ],
//            'url' => '/cutoffs/1',
//            'raw' => json_encode([
//                "method" => "upi",
//                "psp" => "paytm",
//                "high" => "5",
//                "medium" => "20",
//            ]),
//            'method' => 'PUT'
//        ],
//        'response' => [
//            'content' => [
//                [
//                    "method" => "upi",
//                    "psp" => "paytm",
//                    "high" => "5",
//                    "medium" => "20",
//                ]
//            ],
//            'status_code'   => 200,
//        ],
//    ],
];
