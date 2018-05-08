<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testGetMultipleInvoicesAndAssertMetricsSent' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => []
            ],
        ],
    ],

    'testCreateInvoiceAndAssertMetricsSent' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'draft' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'invoice',
                'type'   => 'invoice',
                'status' => 'draft',
            ],
        ],
    ],

    // For each of above tests lists various expectations for metric services

    'testGetMultipleInvoicesAndAssertMetricsSentExpectedMetricTags' => [
        'method'                =>  'GET',
        'route'                 =>  'invoice_fetch_multiple',
        'status'                =>  200,
        'rzp_mode'              =>  'other',
        'rzp_key'               =>  'other',
        'rzp_merchant_id'       =>  'other',
        'rzp_oauth_client_id'   =>  'other',
        'rzp_auth'              =>  'other',
        'rzp_internal_app_name' =>  'other',
    ],

    'testCreateInvoiceAndAssertMetricsSentExpectedMetricTags' => [
        'method'                =>  'POST',
        'route'                 =>  'invoice_create',
        'status'                =>  200,
        'rzp_mode'              =>  'other',
        'rzp_key'               =>  'other',
        'rzp_merchant_id'       =>  'other',
        'rzp_oauth_client_id'   =>  'other',
        'rzp_auth'              =>  'other',
        'rzp_internal_app_name' =>  'other',
    ],
];
