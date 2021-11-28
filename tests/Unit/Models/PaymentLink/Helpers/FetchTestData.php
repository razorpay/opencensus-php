<?php

use RZP\Exception\BadRequestValidationFailureException;

return [
    "testFetch" => [
        "validateStatus with valid input: active"   => [
            "validateStatus", ["status", "active"]
        ],
        "validateStatus with valid input: inactive"   => [
            "validateStatus", ["status", "inactive"]
        ],
        "validateStatus with invalid status"   => [
            "validateStatus",
            ["status", "asdasd"],
            BadRequestValidationFailureException::class,
            "Not a valid status: asdasd"
        ],

        "validateStatusReason with valid input: expired"   => [
            "validateStatusReason", ["status_reason", "expired"]
        ],
        "validateStatusReason with valid input: completed"   => [
            "validateStatusReason", ["status_reason", "completed"]
        ],
        "validateStatusReason with valid input: deactivated"   => [
            "validateStatusReason", ["status_reason", "deactivated"]
        ],
        "validateStatusReason with invalid status reason"   => [
            "validateStatusReason",
            ["status_reason", "asdasd"],
            BadRequestValidationFailureException::class,
            "Not a valid status reason: asdasd"
        ],

        "validateViewType with valid input: page"   => [
            "validateViewType", ["type", "page"]
        ],
        "validateViewType with valid input: button"   => [
            "validateViewType", ["type", "button"]
        ],
        "validateViewType with valid input: subscription_button"   => [
            "validateViewType", ["type", "subscription_button"]
        ],
        "validateViewType with invalid status reason"   => [
            "validateViewType",
            ["type", "asdasd"],
            BadRequestValidationFailureException::class,
            "Not a valid view type: asdasd"
        ],
    ]
];
