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
        'rzp_mode'              =>  'test',
        'rzp_key'               =>  'TheTestAuthKey',
        'rzp_merchant_id'       =>  '10000000000000',
        'rzp_oauth_client_id'   =>  'none',
        'rzp_auth'              =>  'private',
        'rzp_internal_app_name' =>  'none',
        'rzp_auth_flow_type'    =>  'key',
        'has_passport'          =>  false,
        'rzp_product'           =>  'primary',
        'rzp_key_source'        =>  'none',
    ],

    'testCreateInvoiceAndAssertMetricsSentExpectedMetricTags' => [
        'method'                =>  'POST',
        'route'                 =>  'invoice_create',
        'status'                =>  200,
        'rzp_mode'              =>  'test',
        'rzp_key'               =>  'TheTestAuthKey',
        'rzp_merchant_id'       =>  '10000000000000',
        'rzp_oauth_client_id'   =>  'none',
        'rzp_auth'              =>  'private',
        'rzp_internal_app_name' =>  'none',
        'rzp_auth_flow_type'    =>  'key',
        'has_passport'          =>  false,
        'rzp_product'           =>  'primary',
        'rzp_key_source'        =>  'none',
    ],
];
