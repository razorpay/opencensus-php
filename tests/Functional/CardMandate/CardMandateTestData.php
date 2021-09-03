<?php

return [
    'testCreateCardMandateAutoPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testCreateCardMandateAutoPaymentVerificationFailed' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testCreateCardMandateAutoPaymentWithAfa' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "notification.2fa_approved",
                "contains"=> [
                    "mandate.notification"
                ],
                "payload"=> [
                    "mandate.notification"=> [
                        "entity"=> [
                            "id"=> "Hs76F3W7cORX0P",
                            "entity"=> "mandate.notification",
                            "status"=> "delivered",
                            "delivered_at"=> 1630693800,
                            "afa_required"=> true,
                            "afa_status"=> "approved",
                            "afa_completed_at"=> 1630693800
                        ]
                    ]
                ],
                "created_at" => 1620712957,
            ],
            'method'    => 'POST',
            'url'       => '/mandate_hq/callback',
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
