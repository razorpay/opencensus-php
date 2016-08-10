<?php

return array(

    'dashboard' => array(
        'url'       =>  env('APP_DASHBOARD_URL'),
        'secret'    =>  env('APP_DASHBOARD_SECRET'),
        'pretend'   =>  env('APP_DASHBOARD_PRETEND'),
        'cloud'     =>  true,
    ),

    'mock_gateways' => array(
        'secret'    =>  env('MOCK_GATEWAY_SECRET'),
    ),

    'cron' => array(
        'secret'    =>  env('CRON_PASSWORD'),
    ),

    'mailgun' => array(
        'url'       =>  'razorpay.com',
        'key'       =>  env('MAILGUN_SECRET'),
        'mock'      =>  env('MAILGUN_MOCK'),
        'secret'    =>  '',
        'from_name' =>  'Team Razorpay',
        'from_email' => 'support@razorpay.com'
    ),

    'emi' => array(
        'password'  =>  env('EMI_FILE_PASSWORD')
    ),

    'slack' => array(
        'team'      => 'razorpay',
        'token'     =>  env('SLACK_TOKEN'),
        'mock'      =>  env('SLACK_MOCK'),
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
);
