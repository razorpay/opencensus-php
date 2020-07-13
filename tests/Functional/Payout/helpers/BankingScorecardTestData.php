<?php

return [
    'testBankingScorecardMailCheck' =>[
        'request' => [
            'url' => '/banking_scorecard',
            'method' => 'POST',
            'content' => [
                'count' => 10
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ],
    ],
];
