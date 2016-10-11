<?php

namespace RZP\Tests\Functional\Subscription;

return [
    'testCreatePlan' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'interval'          => 'month',
                'interval_count'    => 1,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'amount' =>  2000,
                'currency' => 'INR',
                'interval' => 'month',
                'interval_count' => 1,
                'name' => 'test plan',
                'notes' => [],
            ],
        ],
    ],

    'testCreateSubscription' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
            ],
        ],
        'response' => [
            'content' => [
                'quantity' => 1,
            ],
        ],
    ],
    
    'testSubscriptionCharge' => [
        'request' => [
            'url' => '/subscriptions/charge',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],
];