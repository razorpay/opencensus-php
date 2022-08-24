<?php

namespace RZP\Services\Mock;

use RZP\Services\DeveloperConsole as BaseDeveloperConsole;

class DeveloperConsole extends BaseDeveloperConsole
{
    public function sendRequestAndParseResponse(string $path, string $method, string $auth, array $data = []): array
    {
        $response = [];

        switch ($path)
        {
            case 'incoming/search':
            case 'outgoing/search':
                $response = [
                    'result' => [
                        'timestamp' => 1658412183,
                        'request_id' => '6ff0cd0d-e09b-4313-8923-57e3f4f1cabz',
                        'merchant_id' => 'CeSoV4pBMpqFJ4',
                        'mode' => 'test',
                        'request' => [
                            'route_id' => 'NA',
                            'route_name' => 'X-banking',
                            'method' => 'POST',
                            'url' => 'https://beta-api.stage.razorpay.in/v1/reminders/send/live/payment/card_auto_recurring/JIlp6Hyn4ZsuyG',
                            'header' => [
                                'x-razorpay-request-id' => [
                                    'values' => [
                                        '6ff0cd0d-e09b-4313-8923-57e3f4f1cabz'
                                    ]
                                ],
                            ],
                            'body' => [
                                'channels' => [
                                    'sms'
                                ],
                                'reminder_count' => 1
                            ]
                        ],
                        'response' => [
                            'http_status_code' => 200,
                            'header' => [
                                'request-id' => [
                                    'values' => [
                                        'ed8b0140f35dcb9b1467629b7503177e'
                                    ]
                                ],
                                'x-razorpay-request-id' => [
                                    'values' => [
                                        '6ff0cd0d-e09b-4313-8923-57e3f4f1cabz'
                                    ]
                                ]
                            ],
                            'body' => [],
                        ]
                    ],
                ];

        }


        return [
            'body' => $response,
            'code' => 200
        ];
    }
}
