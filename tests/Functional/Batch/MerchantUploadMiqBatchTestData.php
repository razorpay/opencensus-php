<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testValidateFileEntryMerchantUploadMIQSuccess' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testValidateFileHeaderMerchantUploadMIQFailed' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testValidateHTTPSProtocolMerchantUploadMIQFailed' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 0,
                'error_count'       => 1,
            ],
        ],
    ],

    'testValidateWebsiteDetailMerchantUploadMIQSuccess' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testValidateWebsiteDetailMerchantUploadMIQFailed' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 0,
                'error_count'       => 1,
            ],
        ],
    ],

    'testValidateBusinessTypeMerchantUploadMIQFailed' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 0,
                'error_count'       => 1,
            ],
        ],
    ],

    'testCreateBatchMerchantUploadMIQSuccess' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'type'             => 'merchant_upload_miq',
                'total_count'      => 1,
                'status'           => 'created',
                'processed_amount' => 0,
            ],
        ],
    ],

    'testCreateBatchMerchantUploadMIQInvalidPermission' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'merchant_upload_miq',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
        ],
    ]
];
