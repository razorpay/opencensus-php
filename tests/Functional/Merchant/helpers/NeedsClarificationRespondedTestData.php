<?php

namespace RZP\Tests\Functional\Merchant\helpers;

return [
    'needsClarificationResponded' => [
        'request' => [
            'content' => [
                'business_type' => '1',
                'submit' => true
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'activation_status' => 'under_review',
            ],
        ],
    ],
];
