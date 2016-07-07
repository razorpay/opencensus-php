<?php

return array(

    'token' => env('SLACK_TOKEN'),

    'team' => 'razorpay',

    'mock' => env('SLACK_MOCK'),

    'channels'  =>  [
        'low'            => '#transactions',
        'high'           => '#transactions_high',
        'risky'          => '#transactions_risky',
        'reconciliation' => '#reconciliation',
        'highrisk'       => '#transactions_highrisk',
    ]
);
