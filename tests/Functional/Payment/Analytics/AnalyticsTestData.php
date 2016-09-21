<?php

return [

    'testPaymentAnalytics' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://razorpay.com/demo',
        'browser' => 'chrome',
        'os' => 'macos',
        'device' => 'desktop',
        'library' => 'checkoutjs',
        'library_version' => '3846fgjb',
        'platform' => 'browser',
        'platform_version' => '52.0.2743.116',
        'integration' => 'woo_commerce',
        'integration_version' => '0.1.2',
    ],

    'testPaymentAnalyticsOtp' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://razorpay.com/demo',
        'browser' => 'chrome',
        'os' => 'macos',
        'device' => 'desktop',
        'library' => 'checkoutjs',
        'library_version' => '3846fgjb',
        'platform' => 'mobile_sdk',
        'platform_version' => '0.4.12',
        'integration' => 'magento',
        'integration_version' => '3.1.2',
    ],

    'testDataForUserAgentAnomaly' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'browser' => 'safari',
        'platform_version' => '537.36',
        'os' => 'ios',
        'os_version' => '11.0',
        'device' => 'mobile',
    ],

    'testHttpRequestDataForInvalidData' => [
        'browser' => 'others',
        'os' => 'others',
        'device' => 'others',
        'library' => 'others',
        'platform' => 'others',
        'integration' => 'others',
    ]
];