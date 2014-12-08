<?php

return array(
    'DB_DEFAULT_CONNECTION'     => 'live',

    'DB_LIVE_DRIVER'            => 'mysql',
    'DB_LIVE_HOST'              => getenv('WERCKER_MYSQL_HOST'),
    'DB_LIVE_PORT'              => getenv('WERCKER_MYSQL_PORT'),
    'DB_LIVE_DATABASE'          => getenv('WERCKER_MYSQL_DATABASE'),
    'DB_LIVE_USERNAME'          => getenv('WERCKER_MYSQL_USERNAME'),
    'DB_LIVE_PASSWORD'          => getenv('WERCKER_MYSQL_PASSWORD'),

    'DB_TEST_DRIVER'            => 'sqlite',
    'DB_TEST_HOST'              => '',
    'DB_TEST_PORT'              => '',
    'DB_TEST_DATABASE'          => ':memory:',
    'DB_TEST_USERNAME'          => '',
    'DB_TEST_PASSWORD'          => '',

    'CLOUD'                     => false,

    'HDFC_ID'                   => getenv('HDFC_ID'),
    'HDFC_PASSWORD'             => getenv('HDFC_PASSWORD'),
    'HDFC_MOCK'                 => false,
    'ATOM_MOCK'                 => false,

    'EMAIL_MOCK'                => true,

    'APP_DASHBOARD_URL'         => 'https://dashboard.razorpay.com/',
    'APP_DASHBOARD_SECRET'      => '',
    'APP_DASHBOARD_PRETEND'     => true,

    'SLACK_TOKEN'               => '',
    'SLACK_MOCK'                => true,

    'MAILGUN_API_KEY'           => '',
    'MAILGUN_SECRET'            => 'DASHBOARD_AUTH_PASS',
    'MAILGUN_MOCK'              => true,
);
