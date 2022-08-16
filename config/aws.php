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

    'ses_configuration_header' => env('SES_CONFIGURATION_HEADER'),

    'settlement_bucket'     => env('AWS_S3_SETTLEMENT_BUCKET'),
    'analytics_bucket'      => env('AWS_S3_ANALYTICS_BUCKET'),
    'logo_bucket'           => env('AWS_S3_LOGO_BUCKET'),
    'logo_bucket_region'    => env('AWS_S3_LOGO_BUCKET_REGION'),
    'h2h_bucket'            => env('AWS_H2H_BUCKET'),
    'sftp_bucket'           => env('AWS_SFTP_BUCKET'),
    'activation_bucket'     => env('AWS_ACTIVATION_BUCKET'),
    'invoice_bucket'        => env('AWS_S3_INVOICES_BUCKET'),
    'test_bucket'           => env('AWS_S3_TEST_BUCKET'),

    'hdfc_collect_now_settlement_bucket'           => env('AWS_HDFC_COLLECT_NOW_SETTLEMENT_BUCKET'),

    //
    // The below  bucket is used to store input reconciliation files
    // temporatily. Whenever a file is uploaded to this bucket, a request is
    // triggerred to API using a lambda function.
    //
    'recon_input_bucket'         => env('AWS_S3_RECON_INPUT_BUCKET'),
    'recon_sftp_input_bucket'    => env('AWS_S3_RECON_SFTP_INPUT_BUCKET'),

    'fund_transfer_sftp_bucket_config'    => env('AWS_S3_FUND_TRANSFER_SFTP_BUCKET'),

    'payouts_bucket_config' => env('AWS_S3_PAYOUTS_BUCKET'),

    'sns_target_arn'        => [
        'sms'                    => [
            'live' => env('AWS_RAVEN_TARGET_ARN'),
            'test' => env('AWS_RAVEN_TARGET_ARN'),
        ],
        'lumberjack'             => [
            'live' => env('AWS_LUMBERJACK_TARGET_ARN'),
            'test' => env('AWS_LUMBERJACK_TARGET_ARN'),
        ],
        'stage-doppler'          => [
            'live' => env('AWS_DOPPLER_TARGET_ARN'),
            'test' => env('AWS_DOPPLER_TARGET_ARN'),
        ],
        'settlement_transaction' => [
            'live' => env('SETTLEMENTS_TRANSACTION_LIVE'),
            'test' => env('SETTLEMENTS_TRANSACTION_TEST'),
        ],
        'stork'             => [
            'live' => env('AWS_STORK_TARGET_ARN'),
            'test' => env('AWS_STORK_TARGET_ARN'),
        ],
        'ledger_transaction_create' => [
            'live' => env('SNS_LEDGER_TRANSACTION_CREATE_LIVE'),
            'test' => env('SNS_LEDGER_TRANSACTION_CREATE_TEST'),
        ],
        'ledger_account_onboarding' => [
            'live' => env('LEDGER_ACCOUNT_ONBOARDING_LIVE'),
            'test' => env('LEDGER_ACCOUNT_ONBOARDING_TEST'),
        ],
        'settlements_merchants_events' => [
            'live' => env('SNS_SETTLEMENTS_MERCHANTS_EVENTS_LIVE'),
            'test' => env('SNS_SETTLEMENTS_MERCHANTS_EVENTS_TEST'),
        ]
    ],

    'mock' => env('AWS_S3_MOCK')
];
