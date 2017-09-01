<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCapture' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testBulkCapture' => [
        'response' => [
            'content' => [
                'count'   => 0,
                'success' => 0,
                'failure' => 0,
                'failure_payments' => []
            ],
        ],
    ],

    'testCaptureTwice' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],

    'testCaptureWithGatewayCapturedTrue' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCaptureWithDifferentAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH
        ],
    ],

    'testCaptureWithLessAmountThanAuth' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 10000
                ],
            ],
    ],

    'testCaptureWithMoreAmountThanAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH
        ],

    ],

    'testCaptureWithRandomId' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testCaptureAfterRefund' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],

    'testCaptureWithNoAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithZeroAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithNegativeAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithMinAmountAllowedMinusOne' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithMinAmountAllowed' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 100
                ],
            ],
    ],

    'testCaptureWithOverflowingAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testTransactionOnCaptureWithFeeCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithFeeCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithFeeCreditWithOldFlowForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],


    'testTransactionOnCaptureWithAmountCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                //'service_tax'       => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithAmountCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                //'service_tax'       => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithAmountCreditWithOldFlowForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                //'service_tax'       => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithAmountCreditForFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithFeeCreditForFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithAmountCreditForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                //'service_tax'       => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithFeeCreditForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                //'service_tax'       => 3600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],
];
