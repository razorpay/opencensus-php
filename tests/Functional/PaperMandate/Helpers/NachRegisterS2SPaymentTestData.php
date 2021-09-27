<?php

namespace RZP\Tests\Functional\PaperMandate;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePayment' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'razorpay_order_id' => 'order_100000000order',
            ],
        ],
    ],

    'testCreatePaymentForAlreadyOneMorePaymentIsActive' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A form against this order is pending action on the destination bank. A new form cannot be submitted till a status is received',
                    'step'        => 'form_upload',
                    'reason'      => 'form_status_pending',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FORM_STATUS_PENDING
        ],
    ],

    'testCreatePaymentWithFormDataNotMatching' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more of the fields on the NACH form do not match with that in our records',
                    'step'        => 'form_upload',
                    'reason'      => 'form_data_mismatch',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FORM_DATA_MISMATCH
        ],
    ],

    'testCreatePaymentWithoutSignature' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The signature of the customer is either missing or could not be detected',
                    'step'        => 'form_upload',
                    'reason'      => 'form_signature_missing',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FORM_SIGNATURE_IS_MISSING
        ],
    ],

    'testCreatePaymentWithDifferentForm' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The ID of the uploaded form does not match with that in our records',
                    'step'        => 'form_upload',
                    'reason'      => 'form_mismatch',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FORM_MISMATCH
        ],
    ],

    'testCreatePaymentForFormExtractionFailed' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_NACH_EXTRACT_IMAGE_FAILED
        ],
    ],

    'testCreatePaymentWithNotReadableForm' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded image is not clear. This can either be due to poor resolution or because part of the image is cropped',
                    'step'        => 'form_upload',
                    'reason'      => 'image_not_clear',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_IMAGE_NOT_CLEAR
        ],
    ],

    'testCreatePaymentWithInvalidOrderId' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testCreatePaymentWithNonExistingOrderId' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testCreatePaymentWithoutOrderId' => [
        'request' => [
            'content' => [
                'file' => 'file',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The order id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePaymentWithNotSupportedFileType' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file type of the image is not supported',
                    'step'        => 'form_upload',
                    'reason'      => 'unknown_file_type',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_UNKNOWN_FILE_TYPE
        ],
    ],

    'testCreatePaymentWithFileSizeMoreThanLimit' => [
        'request' => [
            'content' => [
                'order_id' => 'order_100000000order',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/nach/file',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file size exceeds the permissible limits',
                    'step'        => 'form_upload',
                    'reason'      => 'file_size_exceeds_limit',
                    'source'      => 'Customer',
                    'metadata'    => [
                        'order_id'   => 'order_100000000order',
                    ],
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FILE_SIZE_EXCEEDS_LIMIT
        ],
    ],

    'hyperVergeExtractNACHOutput' => [
        'email_id' => 'gaurav.kumar12@example.com',
        'amount_in_words' => 'TEN',
        'utility_code' => 'NACH00000000013149',
        'reference_1' => '121211212112121121',
        'bank_name' => 'HDFC BANK',
        'debit_type' => 'maximum_amount',
        'micr' => '',
        'frequency' => 'as_and_when_presented',
        'until_cancelled' => 'true',
        'nach_type' => 'create',
        'account_number' => '1111111111111',
        'nach_date' => '19/08/2019',
        'phone_number' => '9123456780',
        'umrn' => '',
        'company_name' => 'TEST',
        'ifsc_code' => 'HDFC0000123',
        'reference_2' => '121211212112121121',
        'account_type' => 'savings',
        'amount_in_number' => '1000',
        'enhanced_image' => 'djdnj',
        'end_date' => '',
        'sponsor_code' => 'RATN0TREASU',
        'primary_account_holder' => 'TEST',
        'signature_present_primary' => 'yes',
        'secondary_account_holder' => '',
        'signature_present_secondary' => 'no',
        'tertiary_account_holder' => 'THE DON',
        'signature_present_tertiary' => 'no',
        'start_date' => '07/12/2025',
        'form_checksum' => 'XXXXXXX',
    ],
];
