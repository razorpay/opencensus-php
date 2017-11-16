<?php

use Aws\Laravel\AwsServiceProvider;

return [
    'aws' => [
        'bucket_region' => env('AWS_BUCKET_REGION', 'us-east-1'),
        'mock' => env('AWS_S3_MOCK'),

        'settlement_bucket_config' => [
            'name'   => env('AWS_S3_SETTLEMENT_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'logo_bucket_config' => [
            'name'   => env('AWS_S3_LOGO_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'h2h_bucket_config' => [
            'name'   => env('AWS_H2H_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'activation_bucket_config' => [
            'name'   => env('AWS_ACTIVATION_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'invoice_bucket_config' => [
            'name'   => env('AWS_S3_INVOICES_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'recon_bucket_config' => [
            'name'   => env('AWS_S3_RECON_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'customer_bucket_config' => [
            'name'   => env('AWS_S3_CUSTOMER_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'test_bucket_config' => [
            'name'   => env('AWS_S3_TEST_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
    ],

    'local' => [
        'settlement_bucket_config' => [
            'name'   => env('LOCAL_SETTLEMENT_BUCKET', 'settlement_bucket'),
            'region' => null,
        ],
        'logo_bucket_config' => [
            'name'   => env('LOCAL_LOGO_BUCKET', 'logo_bucket'),
            'region' => null,
        ],
        'h2h_bucket_config' => [
            'name'   => env('LOCAL_H2H_BUCKET', 'h2h_bucket'),
            'region' => null,
        ],
        'activation_bucket_config' => [
            'name'   => env('LOCAL_ACTIVATION_BUCKET', 'activation_bucket'),
            'region' => null,
        ],
        'invoice_bucket_config' => [
            'name'   => env('LOCAL_INVOICES_BUCKET', 'invoice_bucket'),
            'region' => null,
        ],
        'recon_bucket_config' => [
            'name'   => env('LOCAL_RECON_BUCKET', 'recon_bucket'),
            'region' => null,
        ],
        'test_bucket_config' => [
            'name'   => env('LOCAL_TEST_BUCKET', 'test_bucket'),
            'region' => null,
        ],
    ]
];
