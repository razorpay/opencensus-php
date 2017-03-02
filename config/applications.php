<?php

return array(
    'dashboard' => array(
        'url'       => env('APP_DASHBOARD_URL'),
        'secret'    => env('APP_DASHBOARD_SECRET'),
        'pretend'   => env('APP_DASHBOARD_PRETEND'),
        'cloud'     => true,
    ),

    'mock_gateways' => array(
        'secret'    => env('MOCK_GATEWAY_SECRET'),
    ),

    'cron' => array(
        'secret'    => env('CRON_PASSWORD'),
    ),

    'h2h' => array(
        'secret'   => env('APP_H2H_SECRET'),
    ),

    'mailgun' => array(
        'url'       => 'razorpay.com',
        'key'       => env('MAILGUN_SECRET'),
        'mock'      => env('MAILGUN_MOCK'),
        'secret'    => env('APP_MAILGUN_SECRET'),
        'from_name' => 'Team Razorpay',
        'from_email' => 'support@razorpay.com'
    ),

    'emi' => array(
        'password'  => env('EMI_FILE_PASSWORD')
    ),

    'slack' => array(
        'team'      => 'razorpay',
        'token'     => env('SLACK_TOKEN'),
        'mock'      => env('SLACK_MOCK'),
    ),

    'hosted' => array(
        'secret'    => env('APP_HOSTED_SECRET'),
    ),

    'card_tokenex' => array(
        'mock'      => env('TOKENEX_MOCK', false),
        'id'        => env('TOKENEX_ID'),
        'key'       => env('TOKENEX_API_KEY'),
        'url'       => env('TOKENEX_API_URL'),
        'scheme'    => env('TOKENEX_TOKEN_SCHEME'),
    ),

    'raven' => array(
        'url'       => env('RAVEN_URL'),
        'secret'    => env('RAVEN_SECRET'),
    ),

    'maxmind' => array(
        'mock'      => env('MAXMIND_MOCK', false),
        'id'        => '115820',
        'secret'    => env('MAXMIND_SECRET'),
        'secretv2'  => env('MAXMIND_V2_SECRET')
    ),

    'kotak' => array(
        'secret'    => env('KOTAK_SECRET'),
    ),

    'lumberjack' => array(
        'url'           => env('LUMBERJACK_URL'),
        'secret'        => env('LUMBERJACK_SECRET'),
        'key'           => env('LUMBERJACK_KEY'),
        'is_mock'       => env('LUMBERJACK_MOCK', false),
        'identifier'    => env('LUMBERJACK_API_IDENTIFIER')
    ),

    'elfin' => [
        'mock'     => env('ELFIN_MOCK', true),
        'services' => env('ELFIN_SERVICES', 'gimli,bitly'),
        'gimli'    => [
            'secret'   => env('GIMLI_SECRET'),
            'base_url' => env('GIMLI_BASE_URL')
        ],
        'bitly'    => [
            'secret'   => env('BITLY_ACCESS_TOKEN_PUBLIC', 'access_token'),
        ],
        'allow_fallback' => true,
    ],

    'exchange'  => [
        'mock'      => env('EXCHANGE_MOCK', false),
        'url'       => env('EXCHANGE_URL'),
        'appId'     => env('EXCHANGE_APP_ID')
    ],
);
