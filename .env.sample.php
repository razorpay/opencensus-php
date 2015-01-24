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

    'HDFC_MOCK'                 => true,
    'ATOM_MOCK'                 => true,
    'MOCK_GATEWAY_SECRET'       => 'random_password',

    'EMAIL_MOCK'                => true,

    'APP_DASHBOARD_URL'     	=> 'http://dashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'      => 'DASHBOARD_AUTH_PASS',
    'APP_DASHBOARD_PRETEND'     => true,

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

    'CRON_PASSWORD'             => 'a923r8u98uwaf98uw9w8fu',
);