<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testValidatePaymentPage' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'payment_page',
                'config' => [
                ]
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 2,
                'error_count'       => 0,
            ],
            'status_code' => 200,
        ],
    ],

    'createPaymentPageForBatchFileUpload' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"pri__ref__id\",\"required\":true,\"title\":\"Roll No\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id1\",\"required\":false,\"title\":\"Class\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testValidatePaymentPageMissingData' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'payment_page',
                'config' => []
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testPaymentPageBatchCreate' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_page',
                'config' => [
                ],
                'name' => 'payment_page',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'payment_page',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];

