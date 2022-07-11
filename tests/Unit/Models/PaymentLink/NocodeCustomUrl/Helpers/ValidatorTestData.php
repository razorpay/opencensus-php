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

        // refereing https://github.com/publicsuffix/list/blob/master/public_suffix_list.dat
        "Valid domain razorpay.shop"                => ["razorpay.shop", true],
        "Valid domain razorpay.aero"                => ["razorpay.aero", true],
        "Valid domain razorpay.flight.aero"         => ["razorpay.flight.aero", true],
        "Valid domain razorpay.federation.aero"     => ["razorpay.federation.aero", true],
        "Valid domain razorpay.cookingchannel"      => ["razorpay.cookingchannel", true],
        "Valid domain razorpay.foodnetwork"         => ["razorpay.foodnetwork", true],
        "Valid domain razorpay.lifeinsurance"       => ["razorpay.lifeinsurance", true],
    ]
];
