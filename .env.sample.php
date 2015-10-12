<?php

return array(
    'ENCRYPTION_KEY'                                    => '4dkTd5lWhN40CkSrnyrRBuRMsSX9exXD',

    'DB_LIVE_DRIVER'                                    => 'mysql',
    'DB_LIVE_HOST'                                      => 'localhost',
    'DB_LIVE_PORT'                                      => '3306',
    'DB_LIVE_DATABASE'                                  => 'api_live',
    'DB_LIVE_USERNAME'                                  => 'user',
    'DB_LIVE_PASSWORD'                                  => 'password',

    'DB_TEST_DRIVER'                                    => 'mysql',
    'DB_TEST_HOST'                                      => 'localhost',
    'DB_TEST_PORT'                                      => '3306',
    'DB_TEST_DATABASE'                                  => 'api_test',
    'DB_TEST_USERNAME'                                  => 'user',
    'DB_TEST_PASSWORD'                                  => 'password',

    'CONTEXT'                                           => 'dev',

    'CLOUD'                                             => false,

    'AMEX_GATEWAY_TEST_HASH_SECRET'                     => 'AE9A3935F5EFF811C1E8F539C3EF9C4A',
    'AMEX_GATEWAY_TEST_MERCHANT_ID'                     => 'test9820447027',
    'AMEX_GATEWAY_TEST_ACCESS_CODE'                     => '8D86AFF5',
    'AMEX_GATEWAY_TEST_AMA_USER'                        => 'razorpayama',
    'AMEX_GATEWAY_TEST_AMA_PASSWORD'                    => 'password0',

    'AXIS_MIGS_GATEWAY_TEST_HASH_SECRET'                => 'random',
    'AXIS_MIGS_GATEWAY_TEST_MERCHANT_ID'                => 'random',
    'AXIS_MIGS_GATEWAY_TEST_ACCESS_CODE'                => 'password',
    'AXIS_MIGS_GATEWAY_TEST_AMA_USER'                   => 'RAZORANDOM',
    'AXIS_MIGS_GATEWAY_TEST_AMA_PASSWORD'               => 'password',

    'AXIS_GENIUS_GATEWAY_TEST_HASH_SECRET'              => 'random',
    'AXIS_GENIUS_GATEWAY_TEST_MERCHANT_ID'              => 'random',
    'AXIS_GENIUS_GATEWAY_TEST_ACCESS_CODE'              => 'password',

    'BILLDESK_GATEWAY_TEST_MERCHANT_ID'                 => 'random',
    'BILLDESK_GATEWAY_TEST_ACCESS_CODE'                 => 'random',
    'BILLDESK_GATEWAY_TEST_HASH_SECRET'                 => 'random',

    'HDFC_GATEWAY_TEST_TERMINAL_ID'                     => 'hdfc_id',
    'HDFC_GATEWAY_TEST_TERMINAL_PASSWORD'               => 'hdfc_password',

    'KOTAK_GATEWAY_TEST_HASH_SECRET'                    => 'random',
    'KOTAK_GATEWAY_TEST_MERCHANT_ID'                    => 'random',
    'KOTAK_GATEWAY_TEST_ACCESS_CODE'                    => 'password',
    'KOTAK_GATEWAY_TEST_TERMINAL_ID'                    => 'randomid',

    'MOBIKWIK_GATEWAY_TEST_HASH_SECRET'                 => 'random_hash_secret',

    'PAYTM_GATEWAY_TEST_MERCHANT_ID'                    => 'randomid',
    'PAYTM_GATEWAY_TEST_HASH_SECRET'                    => 'randomsecret',

    'PAYZAPP_WALLET_TEST_MERCHANT_ID'                   => 'random',
    'PAYZAPP_WALLET_TEST_MERCHANT_APP_ID'               => 'random',
    'PAYZAPP_WALLET_TEST_HASH_SECRET'                   => 'random',
    'PAYZAPP_WALLET_TEST_PG_INSTANCE_ID'                => 'random',
    'PAYZAPP_WALLET_TEST_PG_MERCHANT_ID'                => 'random',

    'NETBANKING_HDFC_GATEWAY_LIVE_HASH_SECRET'          => '000000',

    'NETBANKING_KOTAK_GATEWAY_TEST_HASH_SECRET'         => '',
    'NETBANKING_KOTAK_GATEWAY_LIVE_HASH_SECRET'         => '',

    'AMEX_MOCK'                                         => true,
    'ATOM_MOCK'                                         => true,
    'AXIS_GENIUS_MOCK'                                  => true,
    'AXIS_MIGS_MOCK'                                    => true,
    'BILLDESK_MOCK'                                     => true,
    'HDFC_MOCK'                                         => true,
    'KOTAK_MOCK'                                        => true,
    'MOBIKWIK_MOCK'                                     => true,
    'PAYTM_MOCK'                                        => true,
    'PAYZAPP_MOCK'                                      => true,
    'NETBANKING_HDFC_MOCK'                              => true,
    'NETBANKING_KOTAK_MOCK'                             => true,

    'MOCK_GATEWAY_SECRET'                               => 'random_password',

    'EMAIL_MOCK'                                        => true,

    'APP_DASHBOARD_URL'                                 => 'http://betadashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'                              => 'RANDOM_DASH_PASSWORD',
    'APP_DASHBOARD_PRETEND'                             => true,

    'APP_HOSTED_SECRET'                                 => 'somerandomsecret',

    'SLACK_TOKEN'                                       => '',
    'SLACK_MOCK'                                        => true,

    'MAILGUN_SECRET'                                    => '',
    'MAILGUN_MOCK'                                      => true,

    'QUEUE_DRIVER'                                      => 'sync',

    'AWS_QUEUE_URL'                                     => '',
    'AWS_KEY_ID'                                        => '',
    'AWS_KEY_SECRET'                                    => '',
    'AWS_REGION'                                        => 'us-east-1',

    'AWS_S3_MOCK'                                       => true,
    'AWS_S3_SETTLEMENT_BUCKET'                          => '',

    'CRON_PASSWORD'                                     => 'RANDOM_CRON_PASSWORD',
);
