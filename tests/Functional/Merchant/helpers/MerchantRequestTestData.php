<?php

use RZP\Tests\Functional\Fixtures\Entity\MerchantRequest;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

return [

    'testGetMerchantRequestDetails' => [
        'request' => [
            'url' => '/merchant/requests/%s',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testChangeMerchantRequestStatusToNeedsClarification' => [
        'request' => [
            'url' => '/merchant/requests/%s',
            'method' => 'PATCH',
            'content' => [
                'status'  => 'needs_clarification',
                'comment' => 'test',
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'needs_clarification',
                'merchant_id' => '10000000000000',
                'comment' => 'test',
            ],
        ],
    ],

    'testChangeMerchantRequestStatusWithException' => [
        'request' => [
            'url' => '/merchant/requests/%s',
            'method' => 'PATCH',
            'content' => [
                'status' => 'activated'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid status change',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetMerchantRequestDetailsWithProxyAuth' => [
        'request' => [
            'url' => '/merchant/requests/%s',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testGetMerchantRequestStatusLog' => [
        'request' => [
            'url' => '/merchant/requests/%s/status_log',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity_type' => 'merchant_request',
                        'entity_id'   => MerchantRequest::DEFAULT_MERCHANT_REQUEST_ID,
                        'name'        => 'under_review'
                    ],
                 ],
            ],
        ],
    ],
];
