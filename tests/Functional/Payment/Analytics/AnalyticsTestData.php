<?php

// @codingStandardsIgnoreStart
return [

    'testPaymentAnalytics' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://pay.com/demo',
        'browser' => 'chrome',
        'os' => 'macos',
        'device' => 'desktop',
        'library' => 'checkoutjs',
        'library_version' => '3846fgjb',
        'platform' => 'browser',
        'platform_version' => '52.0.2743.116',
        'integration' => 'woo_commerce',
        'integration_version' => '0.1.2',
        'ip' => '10.0.123.123'
    ],

    'testHttpRequestDataForNonOtpBasedPayment' => [
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
        'referer' => 'https://pay.com/demo',
        'browser' => 'chrome',
        'os' => 'windows',
        'device' => 'desktop',
        'library' => 'checkoutjs',
        'library_version' => '3846fgjb',
        'platform' => 'browser',
        'platform_version' => '52.0.2743.116',
        'integration' => 'woo_commerce',
        'integration_version' => '0.1.2',
        'ip' => '10.0.123.123'
    ],

    'testHttpRequestDataForS2sPayments' => [
        'user_agent' => 'Razorpay UA',
        'referer' => 'https://pay.com/demo',
        'browser' => null,
        'os' => null,
        'device' => 'desktop',
        'library' => 'direct',
        'library_version' => null,
        'platform' => null,
        'platform_version' => null,
        'integration' => null,
        'integration_version' => null,
        'ip' => '10.0.123.123',
    ],

    'testAnalyticsForS2sPayments' => [
        'merchant_id'         => '10000000000000',
        'checkout_id'         => null,
        'attempts'            => 1,
        'library'             => 'direct',
        'library_version'     => null,
        'browser'             => 'chrome',
        'os'                  => 'windows',
        'os_version'          => '0',
        'device'              => 'desktop',
        'platform'            => null,
        'platform_version'    => '0',
        'integration'         => null,
        'integration_version' => null,
        'ip'                  => '52.34.123.23',
        'referer'             => 'https://pay.com/demo',
        'user_agent'          => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
        'entity'              => 'payment_analytics',
    ],

    'testPaymentAnalyticsOtp' => [
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        'referer' => 'https://pay.com/demo',
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
        'browser' => 'chrome',
        'platform_version' => '537.36',
        'os' => 'ios',
        'os_version' => '11.0',
        'device' => 'mobile',
        'referer' => 'http://a.com',
    ],

    'testHttpRequestDataForInvalidData' => [
        'browser' => null,
        'os' => 'others',
        'device' => 'others',
        'library' => 'others',
        'platform' => 'others',
        'integration' => 'others',
    ]
];
// @codingStandardsIgnoreEnd