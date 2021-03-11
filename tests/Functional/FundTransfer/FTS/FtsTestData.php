<?php

return [
    'testBulkStatus'   => [
        'request'   => [
            'url'     => '/fts/dashboard/fund_transfer_status/bulk',
            'method'  => 'post',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => '400',
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => 'BAD_REQUEST_ACCESS_DENIED',
        ],
    ],
];
