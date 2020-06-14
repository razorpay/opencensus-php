<?php

namespace RZP\Tests\Unit\lib;

return [
    'testTokenOrTokenSetMatch' => [
        [
            'param1'         => 'Tass &         Hamjit Private Limited',
            'param2'         => 'TASS & HAMJIT PRIVATE LIMITED',
            'expected_ratio' => 100
        ],
        [
            'param1'         => 'Kriyative Education',
            'param2'         => 'KRIYATIVE LEARNING SOLUTION INTERNATIONAL PRIVATE LIMITED',
            'expected_ratio' => 64
        ],
        [
            'param1'         => 'KCS QUALITY INNOVATION PVT LTD',
            'param2'         => 'KCS QUALITY INNOVATION PRIVATE LIMITED',
            'expected_ratio' => 88
        ],
    ]
];
