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
                    "public_error_code"   => "SYSTEM_ERROR",
                    "internal_error_code" => "FALLBACK_ERROR",
                    "description"         => "Something went wrong, please try again later. Any amount deducted will be refunded within 5-7 working days."
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
                        ],
                        'YA' => [
                            "public_error_code"   => "GATEWAY_ERROR",
                            "internal_error_code" => "GATEWAY_ERROR_LOST_OR_STOLEN_CARD_REMITTER",
                            "error_description"   => "Card used while setting UPI PIN has been restricted by your bank, please reach out to your bank for more information or use another bank account for payment",
                        ],
                        'AM' => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_BAD_REQUEST_UPI_MPIN_NOT_SET",
                            "error_description"   => "Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.",
                        ],
                        'XM' => [
                            "public_error_code"   => "BAD_REQUEST_ERROR",
                            "internal_error_code" => "BAD_REQUEST_BAD_REQUEST_UPI_MPIN_NOT_SET",
                            "error_description"   => "Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.",
                        ],
                        'ZP' => [
                            "public_error_code"   => "GATEWAY_ERROR",
                            "internal_error_code" => "GATEWAY_ERROR_BANK_NOT_REGISTERED_FOR_UPI",
                            "error_description"   => "The selected bank does not support UPI. Please try with some other bank",
                        ]
                    ],
                    'common'        => [],
                    'fallback'      => [
                        "public_error_code"   => "SYSTEM_ERROR",
                        "internal_error_code" => "FALLBACK_ERROR",
                        "description"         => "Something went wrong, please try again later. Any amount deducted will be refunded within 5-7 working days."
                    ]
                ]
            ]
        ]
    ]
];
