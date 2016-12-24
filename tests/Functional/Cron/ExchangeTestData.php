<?php

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testExchangeRatesLatest' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
            'url' => '/international/USD/rates',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'success' => true
            ],
        ],
    ],
];
