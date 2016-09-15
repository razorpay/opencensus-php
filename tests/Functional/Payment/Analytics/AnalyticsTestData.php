<?php

return [

    'testPaymentAnalytics' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://razorpay.com/demo',
        'browser' => 1,
        'os' => 3,
        'device' => 1,
        'library' => 1,
        'library_version' => '3846fgjb',
        'platform' => 1,
        'platform_version' => '52.0.2743.116',
        'integration' => 1,
        'integration_version' => '0.1.2',
    ],

    'testPaymentAnalyticsOtp' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://razorpay.com/demo',
        'browser' => 1,
        'os' => 3,
        'device' => 1,
        'library' => 1,
        'library_version' => '3846fgjb',
        'platform' => 2,
        'platform_version' => '0.4.12',
        'integration' => 2,
        'integration_version' => '3.1.2',
    ],

    'testDataForUserAgentAnomaly' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'browser' => 4,
        'platform_version' => '537.36',
        'os' => 5,
        'os_version' => '11.0',
        'device' => 3,
    ],

    'testHttpRequestDataForInvalidData' => [
        'browser' => 99,
        'os' => 99,
        'device' => 99,
        'library' => 99,
        'platform' => 99,
        'integration' => 99,
    ]
];