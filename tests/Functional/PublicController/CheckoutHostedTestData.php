<?php

return [
    "testHdfcCheckout2Hit" => [
        "request" => [
            "url" => "/checkout/hosted",
            "method" => "POST",
            "content" => [
                "checkout" => [
                    "key" => " rzp_test_LtX0CbrmyiGV5j",
                    "order_id" => "order_OIALK8qDPyYT0X",
                    "name" => "Shopify Test Store",
                    "prefill" => [
                        "email" => "test@razorpay.com",
                    ],
                    "notes" => [
                        "mode" => "test",
                        "shopify_order_id" => "rQFI1IJ6T2yPiryRCTGjZ3pLx",
                        "referer_url" =>
                            " https://shoes-store-testing-rzp.myshopify.com/",
                    ],
                    "_" => [
                        "integration" => "shopify",
                        "integration_version" => "shopify-payment-app",
                    ],
                    "__referer" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
                "url" => [
                    "callback" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                    "cancel" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
            ],
        ],
        "response" => [
            "content" => [
                "type" => "hdfc_checkout_2",
            ],
        ],
    ],
    "testHdfcCheckout2HitLiveMode" => [
        "request" => [
            "url" => "/checkout/hosted",
            "method" => "POST",
            "content" => [
                "checkout" => [
                    "key" => " rzp_live_LtX0CbrmyiGV5j",
                    "order_id" => "order_OIALK8qDPyYT0X",
                    "name" => "Shopify Test Store",
                    "prefill" => [
                        "email" => "test@razorpay.com",
                    ],
                    "notes" => [
                        "mode" => "live",
                        "shopify_order_id" => "rQFI1IJ6T2yPiryRCTGjZ3pLx",
                        "referer_url" =>
                            " https://shoes-store-testing-rzp.myshopify.com/",
                    ],
                    "_" => [
                        "integration" => "shopify",
                        "integration_version" => "shopify-payment-app",
                    ],
                    "__referer" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
                "url" => [
                    "callback" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                    "cancel" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
            ],
        ],
        "response" => [
            "content" => [
                "type" => "hdfc_checkout_2",
            ],
        ],
    ],
    "testHdfcCheckout2NotHit" => [
        "request" => [
            "url" => "/checkout/hosted",
            "method" => "POST",
            "content" => [
                "checkout" => [
                    "key" => " rzp_test_LtX0CbrmyiGV5j",
                    "order_id" => "order_OIALK8qDPyYT0X",
                    "name" => "Shopify Test Store",
                    "prefill" => [
                        "email" => "test@razorpay.com",
                    ],
                    "notes" => [
                        "mode" => "test",
                        "shopify_order_id" => "rQFI1IJ6T2yPiryRCTGjZ3pLx",
                        "referer_url" =>
                            " https://shoes-store-testing-rzp.myshopify.com/",
                    ],
                    "_" => [
                        "integration" => "shopify",
                        "integration_version" => "shopify-payment-app",
                    ],
                    "__referer" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
                "url" => [
                    "callback" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                    "cancel" =>
                        "https://shoes-store-testing-rzp.myshopify.com/",
                ],
            ],
        ],
        "response" => [
            "content" => [
                "type" => "hosted",
            ],
        ],
    ],
];
