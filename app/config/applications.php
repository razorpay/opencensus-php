<?php

return array(

    'dashboard' => array(
        'url'       =>  $_ENV['APP_DASHBOARD_URL'],
        'secret'    =>  $_ENV['APP_DASHBOARD_SECRET'],
        'pretend'   =>  $_ENV['APP_DASHBOARD_PRETEND'],
        'cloud'     =>  true,
    ),

    'mock_gateways' => array(
        'secret'    =>  $_ENV['MOCK_GATEWAY_SECRET'],
    ),

    'cron' => array(
        'secret'    =>  $_ENV['CRON_PASSWORD'],
    ),

    'mailgun' => array(
        'url'       =>  'mg.razorpay.com',
        'key'       =>  $_ENV['MAILGUN_API_KEY'],
        'secret'    =>  $_ENV['MAILGUN_SECRET'],
        'mock'      =>  $_ENV['MAILGUN_MOCK'],
    ),

    'slack' => array(
        'team'      => 'razorpay',
        'token'     =>  $_ENV['SLACK_TOKEN'],
        'mock'      =>  $_ENV['SLACK_MOCK'],
    )
);