<?php

use Aws\Laravel\AwsServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. The minimum
    | required options are declared here, but the full set of possible options
    | are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    */

    'region' => env('AWS_REGION', 'us-east-1'),
    'bucket_region' => env('AWS_BUCKET_REGION', 'us-east-1'),
    'version' => 'latest',
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],

    'settlement_bucket'     => env('AWS_S3_SETTLEMENT_BUCKET'),
    'analytics_bucket'      => env('AWS_S3_ANALYTICS_BUCKET'),
    'logo_bucket'           => env('AWS_S3_LOGO_BUCKET'),
    'h2h_bucket'            => env('AWS_H2H_BUCKET'),
    'sftp_bucket'           => env('AWS_SFTP_BUCKET'),
    'activation_bucket'     => env('AWS_ACTIVATION_BUCKET'),
    'invoice_bucket'        => env('AWS_S3_INVOICES_BUCKET'),
    'test_bucket'           => env('AWS_S3_TEST_BUCKET'),
    //
    // The below  bucket is used to store input reconciliation files
    // temporatily. Whenever a file is uploaded to this bucket, a request is
    // triggerred to API using a lambda function.
    //
    'recon_input_bucket'         => env('AWS_S3_RECON_INPUT_BUCKET'),
    'recon_sftp_input_bucket'    => env('AWS_S3_RECON_SFTP_INPUT_BUCKET'),

    'sns_target_arn'        => [
        'sms'               => env('AWS_RAVEN_TARGET_ARN'),
        'lumberjack'        => env('AWS_LUMBERJACK_TARGET_ARN'),
        'stage-doppler'     => env('AWS_DOPPLER_TARGET_ARN'),
        'stork'             => env('AWS_STORK_TARGET_ARN'),
    ],

    'mock' => env('AWS_S3_MOCK')
];
