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
    'testSIHUBCardMandateFlow' => [
        'request' => [
            'content' => [],
            'method'    => 'DELETE',
            'url'       => '/subscription_registration/tokens/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testCreateCardMandatePaymentWithAuthLink' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => 50000,
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'card',
                    'max_amount' => 123400,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 50000,
                'currency'     => 'INR',
                'type'         => 'link',
            ],
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

    'runSIHubCreateCardMandateAutoPayment' => [
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
                            "afa_completed_at"=> 1630693800,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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
    'testCreateCardMandateAutoPaymentWithAfaUsingTokenisedCard' => [
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
                            "afa_completed_at"=> 1630693800,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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
    'testCreateCardMandateOptOutOfPayment' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "notification.2fa_rejected",
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
                            "afa_required"=> false,
                            "afa_status"=> "rejected",
                            "afa_completed_at"=> 1630693800,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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
                            "afa_completed_at"=> 1630693800,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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
    'testSubscriptionRegistrationAutoTokenizedCardMandatePaymentAmountGreaterThanMaxAmountWithAFA' => [
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
                            "afa_completed_at"=> 1630693800,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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

    'testRupayHubCreateCardMandateAutoPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateCardMandateAutoPaymentDuplicateNotificationDeliveryCallback' => [
        'request' => [
            'content' => [
                "entity" => "event",
                "event"=> "notification.delivered",
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
                            "afa_required"=> false,
                            "afa_status"=> null,
                            "afa_completed_at"=> 0,
                            "amount" => 5000,
                            "currency" => "INR",
                            "purpose" => "test",
                            "notes" => [
                                "key" => "value",
                            ],
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
    'testCreateRupaySICardMandatePaymentWithAuthLink' => [
        'request'  => [
            'url'     => '/subscription_registration/auth_links',
            'method'  => 'post',
            'content' => [
                'type'        => 'link',
                'amount'      => 50000,
                'receipt'     => '00000000000001',
                'customer'    => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'description' => 'test description',

                'subscription_registration' => [
                    'method' => 'card',
                    'max_amount' => 123400,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],

                'status'       => 'issued',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'amount'       => 50000,
                'currency'     => 'INR',
                'type'         => 'link',
            ],
        ],
    ],
];
