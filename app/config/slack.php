<?php

return array(

    'token' => $_ENV['SLACK_TOKEN'],

    'team' => 'razorpay',

    'mock' => $_ENV['SLACK_MOCK'],

    'channels'  =>  [
        'low'   =>  '#transactions',
        'high'  =>  '#transactions_high'
    ]
);
