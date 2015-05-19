I<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAddIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin' => '112333',
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
        'response' => [
            'content' => [
                'iin' => '112333',
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
    ],
];