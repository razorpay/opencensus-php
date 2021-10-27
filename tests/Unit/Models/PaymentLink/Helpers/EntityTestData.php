<?php

use RZP\Models\Currency\Currency;
use RZP\Models\PaymentLink\Status;
use RZP\Models\PaymentLink\StatusReason;

/**
 * <method_name> => ["test message" => [...attributes]]
 */

$desc = "Lorem ipsum dolor sit, amet consectetur adipisicing elit. Suscipit quibusdam laborum distinctio officiis quos dolor placeat. Reiciendis modi ut delectus.";
$meta = "Meta Description";
$json = '{"metaText": "'.$meta.'"}';

return [
    "testGetCurrency" => [
        "INR Currencies"    => [Currency::INR, Currency::INR],
        "Null Currency"     => [null, Currency::INR],
        "AEDCurrency"       => [Currency::AED, Currency::AED]
    ],

    "testGetDescriptionAndMetaDescription"  => [
        "Text Description"  => [$desc, $desc, $desc],
        "null Description"  => [null, null, null],
        "meta Description"  => [$json, $json, $meta],
        "json description"  => ['{"a":"b"}', '{"a":"b"}', '{"a":"b"}'],
    ],

    "testGetTitle"  => [
        "valid string test" => ["Some Text", "Some Text"],
        "null as text"      => [null, null],
    ],

    "testGetTerms"  => [
        "valid string test" => ["Some Text", "Some Text"],
        "null as text"      => [null, null],
    ],

    "testIsExpired" => [
        "INACTIVE status and Expired Reason"    => [Status::INACTIVE, StatusReason::EXPIRED, true],
        "INACTIVE status and Completed Reason"  => [Status::INACTIVE, StatusReason::COMPLETED, false],
        "ACTIVE status and Completed Reason"    => [Status::ACTIVE, StatusReason::COMPLETED, false],
        "ACTIVE status and Expired Reason"      => [Status::ACTIVE, StatusReason::EXPIRED, false],
        "random status and random Reason"       => ["asdad", "asdad", false],
    ],

    "testIsCompleted"   => [
        "INACTIVE status and Expired Reason"    => [Status::INACTIVE, StatusReason::EXPIRED, false],
        "INACTIVE status and Completed Reason"  => [Status::INACTIVE, StatusReason::COMPLETED, true],
        "ACTIVE status and Completed Reason"    => [Status::ACTIVE, StatusReason::COMPLETED, false],
        "ACTIVE status and Expired Reason"      => [Status::ACTIVE, StatusReason::EXPIRED, false],
        "random status and random Reason"       => ["asdad", "asdad", false],
    ],

    "testGetSelectedInputField" => [
        "phone as selected field"   => ["phone"],
        "email as selected field"   => ["email"]
    ],

    'testGetCapturedPaymentsCount' => [
        'request'  => [
            'url'     => '/payment_pages/pl_100000000000pl/order',
            'method'  => 'post',
            'content' => [
                "line_items" => [
                    [
                        "payment_page_item_id" => "ppi_10000000000ppi",
                        "amount" => 5000,
                        "quantity" => 1,
                    ],
                    [
                        "payment_page_item_id" => "ppi_10000000001ppi",
                        "amount" => 10000,
                        "quantity" => 2,
                    ]
                ],
                "notes" => [
                    "email" => "some@email.com",
                    "phone" => "898989898",
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
