<?php

return array(
    'ENCRYPTION_KEY'            => '4dkTd5lWhN40CkSrnyrRBuRMsSX9exXD',

    'DB_LIVE_DRIVER'            => 'mysql',
    'DB_LIVE_HOST'              => 'localhost',
    'DB_LIVE_PORT'              => '3306',
    'DB_LIVE_DATABASE'          => 'api_live',
    'DB_LIVE_USERNAME'          => 'user',
    'DB_LIVE_PASSWORD'          => 'password',

    'DB_TEST_DRIVER'            => 'mysql',
    'DB_TEST_HOST'              => 'localhost',
    'DB_TEST_PORT'              => '3306',
    'DB_TEST_DATABASE'          => 'api_test',
    'DB_TEST_USERNAME'          => 'user',
    'DB_TEST_PASSWORD'          => 'password',

    'CONTEXT'                   => 'dev',

    'CLOUD'                     => false,

    'HDFC_ID'                   => 'hdfc_id',
    'HDFC_PASSWORD'             => 'hdfc_password',

    'AXIS_GATEWAY_TEST_HASH_SECRET'         => 'random',
    'AXIS_GATEWAY_TEST_MERCHANT_ID'         => 'random',
    'AXIS_GATEWAY_TEST_ACCESS_CODE'         => 'random',

    'HDFC_MOCK'                 => true,
    'ATOM_MOCK'                 => true,
    'AXIS_MOCK'                 => false,

    'MOCK_GATEWAY_SECRET'       => 'random_password',

    'EMAIL_MOCK'                => true,

    'APP_DASHBOARD_URL'     	=> 'http://betadashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'      => 'RANDOM_DASH_PASSWORD',
    'APP_DASHBOARD_PRETEND'     => true,

    'APP_HOSTED_SECRET'         => 'somerandomsecret',

    'SLACK_TOKEN'               => '',
    'SLACK_MOCK'                => true,

    'MAILGUN_API_KEY'           => '',
    'MAILGUN_SECRET'            => '',
    'MAILGUN_MOCK'              => true,

    'QUEUE_DRIVER'              => 'sync',

    'AWS_QUEUE_URL'             => '',
    'AWS_KEY_ID'                => '',
    'AWS_KEY_SECRET'            => '',
    'AWS_REGION'                => 'us-east-1',

    'AWS_S3_MOCK'               => true,
    'AWS_S3_SETTLEMENT_BUCKET'  => '',

    'CRON_PASSWORD'             => 'RANDOM_CRON_PASSWORD',
);