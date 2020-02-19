<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testInstantRefundSuccessful' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],
];
