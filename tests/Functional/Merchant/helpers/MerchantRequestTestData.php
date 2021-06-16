<?php

use RZP\Tests\Functional\Fixtures\Entity\MerchantRequest;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testGetMerchantRequestDetails' => [
        'request'  => [
            'url'    => '/merchant/requests/%s',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testChangeMerchantRequestStatusToNeedsClarification' => [
        'request'  => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'status'           => 'needs_clarification',
                'internal_comment' => 'test',
            ],
        ],
        'response' => [
            'content' => [
                'status'           => 'needs_clarification',
                'merchant_id'      => '10000000000000',
                'internal_comment' => 'test',
            ],
        ],
    ],

    'testChangeMerchantRequestStatusToRejectedWithRejectionReasons' => [
        'request'  => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'status'            => 'rejected',
                'internal_comment'  => 'test',
                'rejection_reason' => [
                    "reason_code"     => "invalid_use_case",
                    "reason_category" => "invalid_use_case",
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'           => 'rejected',
                'merchant_id'      => '10000000000000',
                'internal_comment' => 'test',
                'states'           => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                        [
                            'name' => 'rejected',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testChangeMerchantRequestStatusWithException' => [
        'request'   => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid status change',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetMerchantRequestStatusLog' => [
        'request'  => [
            'url'    => '/merchant/requests/%s/status_log',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity_type' => 'merchant_request',
                        'entity_id'   => MerchantRequest::DEFAULT_MERCHANT_REQUEST_ID,
                        'name'        => 'under_review',
                    ],
                ],
            ],
        ],
    ],

    'testCreateMerchantRequest' => [
        'request'  => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name'        => 'marketplace',
                'type'        => 'product',
                'submissions' => [
                    'settling_to' => 'Myself',
                    'use_case'    => 'Some new dummy use case if you care',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'name'        => 'marketplace',
                'submissions' => [
                    'settling_to' => 'Myself',
                    'use_case'    => 'Some new dummy use case if you care',
                ],
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateMerchantRequestForQrCodeActivation' => [
        'request'  => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name'        => 'qr_codes',
                'type'        => 'product',
                'submissions' => [
                    'business_model' => 'null-value',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'activated',
                'name'        => 'qr_codes',
                'submissions' => [
                    'business_model' => 'null-value',
                ],
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testBulkUpdateMerchantRequests' => [
        'request'  => [
            'url'     => '/merchant/requests/bulk',
            'method'  => 'PUT',
            'content' => [
                '10000000000000' => [
                    [
                        'name'   => 'marketplace',
                        'type'   => 'product',
                        'status' => 'rejected',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success'     => 1,
                'failed'      => 0,
                'failedItems' => [],
            ],
        ],
    ],

    'bulkUpdateMerchantRequestsTimestampsOnce' => [
        'request'  => [
            'url'     => '/merchant/requests/bulk',
            'method'  => 'PUT',
            'content' => [
                '10000000000000' => [
                    [
                        'name'       => 'marketplace',
                        'type'       => 'product',
                        'status'     => 'rejected',
                        'created_at' => 1527501443,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success'     => 1,
                'failed'      => 0,
                'failedItems' => [],
            ],
        ],
    ],

    'testBulkUpdateMerchantRequestsTimestamps' => [
        'request'  => [
            'url'     => '/merchant/requests/bulk',
            'method'  => 'PUT',
            'content' => [
                '10000000000000' => [
                    [
                        'name'       => 'marketplace',
                        'type'       => 'product',
                        'status'     => 'rejected',
                        'created_at' => 1527500000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success'     => 1,
                'failed'      => 0,
                'failedItems' => [],
            ],
        ],
    ],

    'testBulkUpdateMerchantRequestsWithErrors' => [
        'request'  => [
            'url'     => '/merchant/requests/bulk',
            'method'  => 'PUT',
            'content' => [
                '10000000000001' => [
                    [
                        'name'   => 'marketplace',
                        'type'   => 'product',
                        'status' => 'rejected',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success'     => 0,
                'failed'      => 1,
                'failedItems' => [
                    [
                        'name'        => 'marketplace',
                        'type'        => 'product',
                        'status'      => 'rejected',
                        'merchant_id' => '10000000000001',
                        'error'       => 'Unknown Merchant, hence feature not updated',
                    ],
                ],
            ],
        ],
    ],

    'testFetchMerchantRequests' => [
        'request'  => [
            'url'     => '/merchant/requests',
            'method'  => 'GET',
            'content' => [
                'type' => 'product',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'status' => 'under_review',
                        'name'   => 'subscriptions',
                    ],
                ],
            ],
        ],
    ],

    'testGetForFeatureTypeAndName' => [
        'request'  => [
            'url'    => '/merchant/requests/product/subscriptions',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status'    => 'under_review',
                'name'      => 'subscriptions',
                'type'      => 'product',
                'questions' => [
                    'subscriptions' => [

                    ],
                ],
            ],
        ],
    ],

    'testGetForFeatureTypeAndNameWhichDoesNotExist' => [
        'request'  => [
            'url'    => '/merchant/requests/product/marketplace',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'questions' => [
                    'marketplace' => [

                    ],
                ],
            ],
        ],
    ],

    'testCreateMerchantRequestWithErrors' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name' => 'marketplace',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The type field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantRequest' => [
        'request'  => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'internal_comment' => 'test internal comment',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'internal_comment' => 'test internal comment',
            ],
        ],
    ],

    'testUpdateMerchantRequestWithSubmissions' => [
        'request'  => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'internal_comment' => 'test internal comment',
                'submissions'      => [
                    'settling_to' => 'Someone else',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'internal_comment' => 'test internal comment',
                'submissions'      => [
                    'settling_to' => 'Someone else',
                ],
            ],
        ],
    ],

    'testUpdateMerchantRequestWithErrors' => [
        'request'   => [
            'url'     => '/merchant/requests/%s',
            'method'  => 'PATCH',
            'content' => [
                'status'            => 'rejected',
                'rejection_reason'  => [],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The rejection reason field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
