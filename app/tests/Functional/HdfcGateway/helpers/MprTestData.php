<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testUploadMpr' => [
        'request' => [
            'content' => [
                'gateway' => 'mockhdfc',
            ],
            'url' => '/gateway/mpr',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],
];