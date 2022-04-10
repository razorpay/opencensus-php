<?php

use RZP\Models\PaymentLink\ViewType;

return [
    'testValidateProduct'   => [
        "Page product"                  => [ViewType::PAGE, true],
        "Button product"                => [ViewType::BUTTON, true],
        "Subscription Button product"   => [ViewType::SUBSCRIPTION_BUTTON, true],
        "Handle product"                => [ViewType::PAYMENT_HANDLE, true],
        "Invalid Product"               => ["RANDOM", false],
    ],
    'testValidateDomain'    => [
        "valid case razorpay.com"                   => ["razorpay.com", true],
        "valid case with api.razorpay.com"          => ["api.razorpay.com", true],
        "valid case api-web.perf.razorpay.in"       => ["api-web.perf.razorpay.in", true],

        "invalid case with random string"           => ["asdasdasdasd", false],
        "invalid case with random IP"               => ["192.168.0.1", false],
        "invalid case with protocol"                => ["https://razorpay.com", false],
        "invalid case with path"                    => ["razorpay.com/home", false],
        "invalid case with protocol and path"       => ["https://razorpay.com/home", false],
        "invalid case with subdomain and path"      => ["api.razorpay.com/commit", false],
    ]
];
