<?php

return array(
    'ENCRYPTION_KEY'                        => '4dkTd5lWhN40CkSrnyrRBuRMsSX9exXD',

    'DB_LIVE_DRIVER'                        => 'sqlite',
    'DB_LIVE_HOST'                          => '',
    'DB_LIVE_PORT'                          => '',
    'DB_LIVE_DATABASE'                      => ':memory:',
    'DB_LIVE_USERNAME'                      => '',
    'DB_LIVE_PASSWORD'                      => '',

    'DB_TEST_DRIVER'                        => 'mysql',
    'DB_TEST_HOST'                          => getenv('WERCKER_MYSQL_HOST'),
    'DB_TEST_PORT'                          => getenv('WERCKER_MYSQL_PORT'),
    'DB_TEST_DATABASE'                      => getenv('WERCKER_MYSQL_DATABASE'),
    'DB_TEST_USERNAME'                      => getenv('WERCKER_MYSQL_USERNAME'),
    'DB_TEST_PASSWORD'                      => getenv('WERCKER_MYSQL_PASSWORD'),

    'CONTEXT'                               => 'testing',

    'CLOUD'                                 => false,

    'HDFC_ID'                               => getenv('HDFC_ID'),
    'HDFC_PASSWORD'                         => getenv('HDFC_PASSWORD'),

    'AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'    => getenv('AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'),
    'AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'    => getenv('AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'),
    'AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'    => getenv('AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'),

    'AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'  => 'random',
    'AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'  => 'random',
    'AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'  => 'randompwd',

    'MOCK_GATEWAY_SECRET'                   => 'wercker_random_password',

    'HDFC_MOCK'                             => true,
    'ATOM_MOCK'                             => true,
    'AXIS_MIGS_MOCK'                        => true,
    'AXIS_GENIUS_MOCK'                      => true,

    'EMAIL_MOCK'                            => true,

    'APP_DASHBOARD_URL'                     => 'https://dashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'                  => 'DASHBOARD_AUTH_PASS',
    'APP_DASHBOARD_PRETEND'                 => true,

    'APP_HOSTED_SECRET'                     => 'somerandomsecret',

    'SLACK_TOKEN'                           => '',
    'SLACK_MOCK'                            => true,

    'MAILGUN_API_KEY'                       => getenv('MAILGUN_API_KEY'),
    'MAILGUN_SECRET'                        => 'DASHBOARD_AUTH_PASS',
    'MAILGUN_MOCK'                          => false,

    'QUEUE_DRIVER'                          => 'sync',

    'AWS_QUEUE_URL'                         => '',
    'AWS_KEY_ID'                            => '',
    'AWS_KEY_SECRET'                        => '',
    'AWS_REGION'                            => 'us-east-1',

    'AWS_S3_MOCK'                           => true,
    'AWS_S3_SETTLEMENT_BUCKET'              => '',

    'CRON_PASSWORD'                         => 'a923r8u98uwaf98uw9w8fu',
);
