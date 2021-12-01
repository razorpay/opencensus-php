<?php

use Aws\Laravel\AwsServiceProvider;

use Aws\Credentials\CredentialProvider;

use RZP\Services\Aws\Credentials\InstanceProfileProvider;

$instanceProfileProvider = new InstanceProfileProvider(["timeout" => 10]);
$provider = $instanceProfileProvider->getProvider();
$memoizedProvider = CredentialProvider::memoize($provider);

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
            'region' => env('AWS_RECON_BUCKET_REGION', 'ap-south-1'),
        ],
        'analytics_bucket_config' => [
            'name'   => env('AWS_S3_ANALYTICS_BUCKET'),
            'region' => env('AWS_ANALYTICS_BUCKET_REGION', 'ap-south-1'),
        ],
        'customer_bucket_config' => [
            'name'   => env('AWS_S3_CUSTOMER_BUCKET'),
            'region' => env('AWS_CUSTOMER_BUCKET_REGION', 'ap-south-1')
        ],
        'test_bucket_config' => [
            'name'   => env('AWS_S3_TEST_BUCKET'),
            'region' => env('AWS_BUCKET_REGION', 'us-east-1')
        ],
        'batch_service_bucket_config' => [
            'name'   => env('AWS_S3_BATCH_BUCKET'),
            'region' => env('AWS_BUCKET_BATCH_REGION', 'ap-south-1')
        ],
        'h2h_default_bucket_config' => [
            'name'   => env('AWS_H2H_DEFAULT_BUCKET'),
            'region' => env('AWS_H2H_DEFAULT_BUCKET_REGION', 'ap-south-1')
        ],
        'beam_bucket_config' => [
            'name'   => env('AWS_S3_BEAM_BUCKET'),
            'region' => env('AWS_S3_BEAM_BUCKET_REGION', 'ap-south-1')
        ],
        'recon_sftp_input_bucket' => [
            'name'   => env('AWS_S3_RECON_SFTP_INPUT_BUCKET'),
            'region' => env('AWS_S3_RECON_SFTP_INPUT_BUCKET_REGION', 'ap-south-1')
        ],
        'fund_transfer_sftp_bucket_config' => [
            'name'   => env('AWS_S3_FUND_TRANSFER_SFTP_BUCKET'),
            'region' => env('AWS_S3_FUND_TRANSFER_SFTP_BUCKET_REGION', 'ap-south-1')
        ],
        'data_lake_segments_bucket_config' => [
            'name'   => env('AWS_S3_DATA_LAKE_SEGMENT_BUCKET'),
            'region' => env('AWS_S3_DATA_LAKE_SEGMENT_REGION', 'ap-south-1'),
            'credentials'=>$memoizedProvider
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
        'analytics_bucket_config' => [
            'name'   => env('LOCAL_ANALYTICS_BUCKET', 'analytics_bucket'),
            'region' => null,
        ],
        'test_bucket_config' => [
            'name'   => env('LOCAL_TEST_BUCKET', 'test_bucket'),
            'region' => null,
        ],
        'h2h_default_bucket_config' => [
            'name'   => env('AWS_H2H_DEFAULT_BUCKET','rzp-np-api-settlements'),
            'region' => env('AWS_H2H_DEFAULT_BUCKET_REGION', 'ap-south-1')
        ],
    ]
];
