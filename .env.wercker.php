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
    'AXIS_MIGS_GATEWAY_TEST_AMA_USER'       => getenv('AXIS_MIGS_GATEWAY_TEST_AMA_USER'),
    'AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'   => getenv('AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'),

    'AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'  => getenv('AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'),
    'AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'  => getenv('AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'),
    'AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'  => getenv('AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'),

    'BILLDESK_GATEWAY_TEST_MERCHANT_ID'     => getenv('BILLDESK_GATEWAY_TEST_MERCHANT_ID'),
    'BILLDESK_GATEWAY_TEST_ACCESS_CODE'     => getenv('BILLDESK_GATEWAY_TEST_ACCESS_CODE'),
    'BILLDESK_GATEWAY_TEST_HASH_SECRET'     => getenv('BILLDESK_GATEWAY_TEST_HASH_SECRET'),

    'HDFC_GATEWAY_TEST_TERMINAL_ID'         => getenv('HDFC_GATEWAY_TEST_TERMINAL_ID'),
    'HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'   => getenv('HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'),

    'KOTAK_GATEWAY_TEST_HASH_SECRET'        => getenv('KOTAK_GATEWAY_TEST_HASH_SECRET'),
    'KOTAK_GATEWAY_TEST_MERCHANT_ID'        => getenv('KOTAK_GATEWAY_TEST_MERCHANT_ID'),
    'KOTAK_GATEWAY_TEST_ACCESS_CODE'        => getenv('KOTAK_GATEWAY_TEST_ACCESS_CODE'),
    'KOTAK_GATEWAY_TEST_TERMINAL_ID'        => getenv('KOTAK_GATEWAY_TEST_TERMINAL_ID'),

    'PAYTM_GATEWAY_TEST_MERCHANT_ID'        => getenv('PAYTM_GATEWAY_TEST_MERCHANT_ID'),
    'PAYTM_GATEWAY_TEST_HASH_SECRET'        => getenv('PAYTM_GATEWAY_TEST_HASH_SECRET'),

    'NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET' => '000000',

    'MOCK_GATEWAY_SECRET'                   => 'wercker_random_password',

    'ATOM_MOCK'                             => true,
    'AXIS_MIGS_MOCK'                        => true,
    'AXIS_GENIUS_MOCK'                      => true,
    'BILLDESK_MOCK'                         => true,
    'HDFC_MOCK'                             => true,
    'KOTAK_MOCK'                            => true,
    'PAYTM_MOCK'                            => true,
    'NETBANKING_HDFC_MOCK'                  => true,

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
