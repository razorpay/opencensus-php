<?php
    return [
        'testInternalInstrumentAdminDashboardProxyMissingPermission'   => [
            'request'   => [

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
        'testMerchantInstrumentKAMDashboardProxyMissingPermission'   => [
            'request'   => [

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
