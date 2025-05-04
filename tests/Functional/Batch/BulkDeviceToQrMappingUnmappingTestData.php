<?php


use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testBulkQrDeviceMappingValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_mapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testBulkQrDeviceMappingMissingData' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'qr_device_mapping',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Uploaded file is missing mandatory header(s) [Device Serial No]',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkQrDeviceMappingValidateFileHavingExtraHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_mapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testBulkQrDeviceMappingValidateFileHavingHeadersInDifferentOrder' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_mapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testBulkQrDeviceUnmappingValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_unmapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testBulkQrDeviceUnmappingMissingData' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'qr_device_unmapping',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Uploaded file is missing mandatory header(s) [UnmapFromQR]',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkQrDeviceUnmappingValidateFileHavingExtraHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_unmapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testBulkQrDeviceUnmappingValidateFileHavingHeadersInDifferentOrder' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'qr_device_unmapping',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],
];
