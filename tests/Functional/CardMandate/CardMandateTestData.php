<?php

return [
    'testCardMandateTokenDelete' => [
        'request' => [
            'content' => [],
            'method'    => 'DELETE',
            'url'       => '/subscription_registration/tokens/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
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
    'testMandateHQCallbackMandatePaused' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "mandate.paused",
                "contains"=> [
                    "mandate"
                ],
                "payload"=> [
                    "mandate"=> [
                        "entity"=> [
                            "id"=> "Hs76F3W7cORX0P",
                            "entity" => "mandate",
                            "status" => "paused",
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
    'testMandateHQCallbackMandateResumed' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "mandate.resumed",
                "contains"=> [
                    "mandate"
                ],
                "payload"=> [
                    "mandate"=> [
                        "entity"=> [
                            "id"=> "Hs76F3W7cORX0P",
                            "entity" => "mandate",
                            "status" => "activated",
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
    'testMandateHQCallbackMandateCancelled' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "mandate.cancelled",
                "contains"=> [
                    "mandate"
                ],
                "payload"=> [
                    "mandate"=> [
                        "entity"=> [
                            "id"=> "Hs76F3W7cORX0P",
                            "entity" => "mandate",
                            "status" => "cancelled",
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
    'testMandateHQCallbackMandateCompleted' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "mandate.completed",
                "contains"=> [
                    "mandate"
                ],
                "payload"=> [
                    "mandate"=> [
                        "entity"=> [
                            "id"=> "Hs76F3W7cORX0P",
                            "entity" => "mandate",
                            "status" => "completed",
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
    'testSubscriptionRegistrationAutoCardMandatePaymentAmountGreaterThanMaxAmountWithAFA' => [
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
