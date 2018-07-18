<?php

use RZP\Constants\Metric;

return [

    'Filter should not happen' => [
        [
            Metric::LABEL_ROUTE => 'order_create',
        ],
        [
            Metric::LABEL_INSTANCE => '10.123.123.123',
            Metric::LABEL_ROUTE    => 'order_create',
        ],
    ],

    'Merchant id should get filtered' => [
        [
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'merchant_00002',
        ],
        [
            Metric::LABEL_INSTANCE        => '10.123.123.123',
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'other',
        ],
    ],

    'Merchant id should not get filtered' => [
        [
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'merchant_00001',
        ],
        [
            Metric::LABEL_INSTANCE        => '10.123.123.123',
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'merchant_00001',
        ],
    ],

    'Merchant id & key id should get filtered' => [
        [
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'merchant_00002',
            Metric::LABEL_RZP_KEY_ID      => 'key_0000000003',
        ],
        [
            Metric::LABEL_INSTANCE        => '10.123.123.123',
            Metric::LABEL_ROUTE           => 'order_create',
            Metric::LABEL_RZP_MERCHANT_ID => 'other',
            Metric::LABEL_RZP_KEY_ID      => 'other',
        ],
    ],
];
