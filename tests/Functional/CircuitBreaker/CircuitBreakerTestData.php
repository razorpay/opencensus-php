<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testAadhaarEkycOpenCircuit' => [
        'request'=>[
            'url'     => '/bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
            'content' => [],
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],
];
