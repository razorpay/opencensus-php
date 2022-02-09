<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFiltersGetByType' => [
        'request'  => [
            'url'     => '/admin/reports/filters/detailed_transaction',
            'method'  => 'get',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testGetReportsData' => [
        'request'  => [
            'url'     => '/admin/reports/detailed_merchant?count=30&skip=5&partner_id=DE7Wb69knrM6U9',
            'method'  => 'get',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testGetReportsByType' => [
        'request'  => [
            'url'     => '/admin/reports/fetch/detailed_transaction',
            'method'  => 'get',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testGetReportsForAdmin' => [
        'request'  => [
            'url'     => '/admin/reports/admin/list',
            'method'  => 'get',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testReportsGetReportById' => [
        'request'  => [
            'url'     => '/admin/reports/download/detailed_transaction',
            'method'  => 'get',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
];
