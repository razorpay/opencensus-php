<?php

use Aws\Laravel\AwsServiceProvider;

return [
    'aws' =>
    [
        'region'        => env('AWS_REGION', 'us-east-1'),
        'bucket_region' => env('AWS_BUCKET_REGION', 'us-east-1'),
        'mock'          => env('AWS_S3_MOCK'),

        'settlement_bucket_config' =>
        [
            'name'   => env('AWS_S3_SETTLEMENT_BUCKET'),
            'region' => env('AWS_S3_SETTLEMENT_BUCKET_REGION', 'us-east-1')
        ],
        'logo_bucket_config' =>
        [
            'name'   => env('AWS_S3_LOGO_BUCKET'),
            'region' => env('AWS_S3_LOGO_BUCKET_REGION', 'us-east-1')
        ],
        'h2h_bucket_config' =>
        [
            'name'   => env('AWS_H2H_BUCKET'),
            'region' => env('AWS_H2H_BUCKET_REGION', 'us-east-1')
        ],
        'activation_bucket_config' =>
        [
            'name'   => env('AWS_ACTIVATION_BUCKET'),
            'region' => env('AWS_ACTIVATION_BUCKET_REGION', 'us-east-1')
        ],
        'invoice_bucket_config' =>
        [
            'name'   => env('AWS_S3_INVOICES_BUCKET'),
            'region' => env('AWS_S3_INVOICES_BUCKET_REGION', 'us-east-1')
        ],
        'test_bucket_config' =>
        [
            'name'   => env('AWS_S3_TEST_BUCKET'),
            'region' => env('AWS_S3_TEST_BUCKET_REGION', 'us-east-1')
        ],
    ],

    'local' =>
    [
        'settlement_bucket_config' =>
        [
            'name'   => 'settlement_bucket',
            'region' => null,
        ],
        'logo_bucket_config' =>
        [
            'name'   => 'logo_bucket',
            'region' => null,
        ],
        'h2h_bucket_config' =>
        [
            'name'   => 'h2h_bucket',
            'region' => null,
        ],
        'activation_bucket_config' =>
        [
            'name'   => 'activation_bucket',
            'region' => null,
        ],
        'invoice_bucket_config' =>
        [
            'name'   => 'invoice_bucket',
            'region' => null,
        ],
        'test_bucket_config' =>
        [
            'name'   => 'test_bucket',
            'region' => null,
        ],
    ]
];
