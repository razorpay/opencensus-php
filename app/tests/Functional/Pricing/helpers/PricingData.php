<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreatePricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'haha',
                'payment_mode' => 'card',
                'payment_mode_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'haha',
                'payment_mode' => 'card',
                'payment_mode_type' => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
        ],
    ],

    'testAddPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_mode' => 'card',
                'payment_mode_type'  => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'haha',
                'payment_mode' => 'card',
                'payment_mode_type' => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
        ],
    ],
];
