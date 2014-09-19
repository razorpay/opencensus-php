<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testUploadMpr' => [
        'request' => [
            'content' => [
                'gateway' => 'hdfc',
            ],
            'url' => '/gateway/mpr/reconcile',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],
];