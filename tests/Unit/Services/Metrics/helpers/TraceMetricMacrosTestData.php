<?php

use RZP\Constants\Metric;

return [
    'Test case #1' => [
        'count',
        [
            'counter#1',
        ],
    ],

    'Test case #2' => [
        'count',
        [
            'counter#2',
            10,
            [
                'label#1' => 'value#1',
                'label#2' => 'value#2',
            ],
        ],
    ],

    'Test case #3' => [
        'gauge',
        [
            'gauge#1',
            10,
        ],
    ],

    'Test case #4' => [
        'histogram',
        [
            'histogram#1',
            10,
            [
                'label#1' => 'value#1',
            ],
        ],
    ],

    'Test case #5' => [
        'summary',
        [
            'summary#1',
            10,
            [
                'label#1' => 'value#1',
                'label#2' => 'value#2',
            ],
        ],
    ],
];
