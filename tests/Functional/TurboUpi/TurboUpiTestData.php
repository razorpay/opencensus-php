<?php


return [
    'testFetchTurboUpiErrorMappings' => [
        'request'  => [
            'url'     => '/upi/turbo/error_mapping',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'gateways' => [
                    'upi_axisolive' => [
                        "XL"  => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED",
                            "description"         => "Payment was unsuccessful as you have breached the limit to enter UPI PIN incorrectly. Try using another method."
                        ],
                        "ZM"  => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_PAYMENT_PIN_INCORRECT",
                            "description"         => "You have entered an incorrect PIN on the UPI app. Please retry with the correct PIN."
                        ],
                        "U90" => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED",
                            "description"         => "User account is locked."
                        ]
                    ]
                ],
                'common'   => [
                ],
                "fallback" => [
                    "public_error_code"   => "SERVER_ERROR",
                    "internal_error_code" => "FALLBACK_ERROR",
                    "description"         => "We are facing some trouble completing your request at the moment. Please try again shortly.",
                    "reason"              => "server_error",
                    'source'              => "internal",
                    "step"                => "payment_authorization"
                ]
            ],
        ],
    ],

    'testSetUpiTurboErrorMappingsAdmin' => [
        'request'  => [
            'url'     => '/admin/upi/turbo/error_mapping',
            'method'  => 'PUT',
            'content' => []
        ],
        'response' => [
            'content' => [
                'gateways' => [
                    'upi_axisolive' => [
                        'XL' => [
                            "public_error_code"   => "GATEWAY_ERROR",
                            "internal_error_code" => "GATEWAY_ERROR_PAYMENT_PIN_ATTEMPTS_EXCEEDED",
                            "error_description"   => "Payment was declined because PIN attempts have exceeded. Please try from another account.",
                            "reason"              => "bad_request_payment_pin_attempts_exceeded",
                            "source"              => "customer",
                            "step"                =>   ""
                        ],
                        'YA' => [
                            "public_error_code"   => "GATEWAY_ERROR",
                            "internal_error_code" => "GATEWAY_ERROR_LOST_OR_STOLEN_CARD_REMITTER",
                            "error_description"   => "Card used while setting UPI PIN has been restricted by your bank, please reach out to your bank for more information or use another bank account for payment",
                            "reason"              => "lost_or_stolen_card_remitter",
                            "source"              => "issuer_bank",
                            "step"                => ""
                        ],
                        'AM' => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_BAD_REQUEST_UPI_MPIN_NOT_SET",
                            "error_description"   => "Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.",
                            "reason"              => "bad_request_upi_mpin_not_set",
                            "source"              => "customer",
                            "step"                => ""
                        ],
                        'XM' => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_BAD_REQUEST_UPI_MPIN_NOT_SET",
                            "error_description"   => "Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.",
                            "reason"              => "registration_card_expired_beneficiary",
                            "source"              => "beneficiary_bank",
                            "step"                => ""
                        ],
                        'ZP' => [
                            "public_error_code"   => "GATEWAY_ERROR",
                            "internal_error_code" => "GATEWAY_ERROR_BANK_NOT_REGISTERED_FOR_UPI",
                            "error_description"   => "The selected bank does not support UPI. Please try with some other bank",
                            "reason"              => "bank_not_registered_for upi",
                            "source"              => "issuer_bank",
                            "step"                => ""
                        ]
                    ],
                    'common'        => [],
                    'fallback'      => [
                        "public_error_code"   => "SERVER_ERROR",
                        "internal_error_code" => "FALLBACK_ERROR",
                        "description"         => "We are facing some trouble completing your request at the moment. Please try again shortly.",
                        "reason"              => "server_error",
                        'source'              => "internal",
                        "step"                => "payment_authorization"
                    ]
                ]
            ]
        ]
    ],

    'testRecordTurboUpiCustomerConsent' => [
        'request'  => [
            'url'     => '/upi/turbo/customer/consent',
            'method'  => 'POST',
            'content' => [
                'type' => 'upi_turbo_prefetch',
                'message' => 'Automatically fetch & link my active UPI accounts from top banks',
                'customer_identifier_type' => 'mobile_number',
                'customer_identifier_value' => '6363123456',
                'acknowledge' => true,
                'timestamp' => time(),
                'metadata' => [
                    'prefetch_bank' => [
                        [
                            "priority"     => "0",
                            "iin"          => "607153",
                            "display_name" => "AXIS",
                            "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
                        ],
                        [
                            "priority"     => "1",
                            "iin"          => "607152",
                            "display_name" => "HDFC",
                            "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
                        ],
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [],
            'http_status_code' => 201
        ],
    ],

    'testTurboUpiCustomerConsentHandlingExceptions' => [
        'errorFromType' => [
            'request'  => [
                'url'     => '/upi/turbo/customer/consent',
                'method'  => 'POST',
                'content' => [
                    'type' => 'dumb_type',
                    'message' => 'Automatically fetch & link my active UPI accounts from top banks',
                    'customer_identifier_type' => 'mobile_number',
                    'customer_identifier_value' => '6363123456',
                    'acknowledge' => true,
                    'timestamp' => time(),
                    'metadata' => [
                        'prefetch_bank' => [
                            [
                                "priority"     => "0",
                                "iin"          => "607153",
                                "display_name" => "AXIS",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
                            ],
                            [
                                "priority"     => "1",
                                "iin"          => "607152",
                                "display_name" => "HDFC",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
                            ],
                        ]
                    ]
                ]
            ],
            'response' => [
                'content' => [],
                'http_status_code' => 400
            ]],
        'errorFromPrefetchBank' => [
            'request'  => [
                'url'     => '/upi/turbo/customer/consent',
                'method'  => 'POST',
                'content' => [
                    'type' => 'upi_turbo_prefetch',
                    'message' => 'Automatically fetch & link my active UPI accounts from top banks',
                    'customer_identifier_type' => 'mobile_number',
                    'customer_identifier_value' => '6363123456',
                    'acknowledge' => true,
                    'timestamp' => time(),
                    'metadata' => [
                        'dumb_banks' => [
                            [
                                "priority"     => "0",
                                "iin"          => "607153",
                                "display_name" => "AXIS",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
                            ],
                            [
                                "priority"     => "1",
                                "iin"          => "607152",
                                "display_name" => "HDFC",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
                            ],
                        ]
                    ]
                ]
            ],
            'response' => [
                'content' => [],
                'http_status_code' => 400
            ]],
        'errorFromBankPriority' => [
            'request'  => [
                'url'     => '/upi/turbo/customer/consent',
                'method'  => 'POST',
                'content' => [
                    'type' => 'upi_turbo_prefetch',
                    'message' => 'Automatically fetch & link my active UPI accounts from top banks',
                    'customer_identifier_type' => 'mobile_number',
                    'customer_identifier_value' => '6363123456',
                    'acknowledge' => true,
                    'timestamp' => time(),
                    'metadata' => [
                        'prefetch_bank' => [
                            [
                                "priority"     => 0,
                                "iin"          => "607153",
                                "display_name" => "AXIS",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
                            ],
                            [
                                "priority"     => "1",
                                "iin"          => "607152",
                                "display_name" => "HDFC",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
                            ],
                        ]
                    ]
                ]
            ],
            'response' => [
                'content' => [],
                'http_status_code' => 400
            ]],
        'errorFromBankDisplayName' => [
            'request'  => [
                'url'     => '/upi/turbo/customer/consent',
                'method'  => 'POST',
                'content' => [
                    'type' => 'upi_turbo_prefetch',
                    'message' => 'Automatically fetch & link my active UPI accounts from top banks',
                    'customer_identifier_type' => 'mobile_number',
                    'customer_identifier_value' => '6363123456',
                    'acknowledge' => true,
                    'timestamp' => time(),
                    'metadata' => [
                        'prefetch_bank' => [
                            [
                                "priority"     => "0",
                                "iin"          => "607153",
                                "display_name" => "AXIS",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
                            ],
                            [
                                "priority"     => "1",
                                "iin"          => "607152",
                                "display_name" => 123,//"HDFC",
                                "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
                            ],
                        ]
                    ]
                ]
            ],
            'response' => [
                'content' => [],
                'http_status_code' => 400
            ]],
    ],
];
