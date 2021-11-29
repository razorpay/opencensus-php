<?php

use RZP\Models\Item;
use RZP\Models\Currency\Currency;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Exception\BadRequestValidationFailureException;

return [
    "testValidateAmount"    => [
        "INR amount less than 100 Paisa"    => [
            1, Currency::INR, BadRequestValidationFailureException::class, "amount must be atleast INR 1"
        ],
        "INR amount greater than 100 Paisa"             => [100],
        "INR amount greater than allowed max amount"    => [
            500000000,
            Currency::INR,
            BadRequestValidationFailureException::class,
            "amount exceeds maximum payment amount allowed"
        ]
    ],

    "testValidateProductConfig" => [
        "Empty config should pass"  => [[]],
        "additional key other than subscription_details should throw error" => [
            ["some_key" => "true"],
            BadRequestValidationFailureException::class,
        ],
        "empty subscription_details should pass" => [["subscription_details" => []]],
        "allowed keys in subscription_details should pass" => [
            [
                "subscription_details"  => [
                    "total_count"       => 100,
                    "customer_notify"   => true,
                    "quantity"          => 1,
                ]
            ]
        ],
        "additional key in subscription_details should throw error" => [
            [
                "subscription_details"  => [
                    "total_count"       => 100,
                    "customer_notify"   => true,
                    "quantity"          => 1,
                    "some_key"          => "sadad"
                ]
            ],
            BadRequestValidationFailureException::class,
        ],
    ],

    "testValidateMaxAmount"  => [
        "valid scenario should pass"  => [
            [
                PaymentPageItem\Entity::MAX_AMOUNT  => 1000,
                PaymentPageItem\Entity::ITEM        => [
                    Item\Entity::CURRENCY   => Currency::INR
                ]
            ]
        ],
        "without item and valid max amount should pass"  => [
            [
                PaymentPageItem\Entity::MAX_AMOUNT  => 1000,
            ]
        ],
        "without item and 1 paisa max amount should fail"  => [
            [
                PaymentPageItem\Entity::MAX_AMOUNT  => 1,
            ],
            BadRequestValidationFailureException::class,
            "max_amount must be atleast INR 1"
        ]
    ],
    "testValidateStock"  => [
        "stock is less should throw exception"  => [
            9,
            BadRequestValidationFailureException::class,
            'stock should not be lesser than already sold quantity'
        ],
        "stock is greater should not throw exception"  => [11],
    ]
];
