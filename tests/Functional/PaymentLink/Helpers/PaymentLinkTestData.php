<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePaymentLink' => [
        'request'  => [
            'url'     => '/payment_links',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
    ],

    'testCreatePaymentLinkWithBadExpireBy' => [
        'request'  => [
            'url'     => '/payment_links',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'expire_by'     => 1400000000,
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => [
                    'sample_key' => 'Sample notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after the current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchPaymentLink' => [
        'request'  => [
            'url'     => '/payment_links/pl_100000000000pl',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => [],
            ],
        ],
    ],

    'testFetchPaymentLinks' => [
        'request'  => [
            'url'     => '/payment_links',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id'            => 'pl_100000000000pl',
                        'receipt'       => '00000000000001',
                        'amount'        => 100000,
                        'currency'      => 'INR',
                        'title'         => 'Sample title',
                        'description'   => 'Sample description',
                        'notes'         => [],
                    ],
                ],
            ],
        ],
    ],

    'testUpdatePaymentLink' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000002',
                'title'         => 'Sample test title',
                'description'   => 'Sample test description',
                'notes'         => [
                    'sample_key' => 'Sample test notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'receipt'       => '00000000000002',
                'title'         => 'Sample test title',
                'description'   => 'Sample test description',
                'notes'         => [
                    'sample_key' => 'Sample test notes',
                ],
            ],
        ],
    ],

    'testUpdatePaymentLinkWithBadExpireBy' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000002',
                'expire_by'     => 1400000000,
                'title'         => 'Sample test title',
                'description'   => 'Sample test description',
                'notes'         => [
                    'sample_key' => 'Sample test notes',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after the current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentLinkSendNotification' => [
        'request'  => [
            'url'     => '/payment_links/pl_100000000000pl/notify',
            'method'  => 'post',
            'content' => [
                'emails'   => ['test@rzp.com'],
                'contacts' => ['9090908080']
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testInactivePaymentLinkSendNotification' => [
        'request'  => [
            'url'     => '/payment_links/pl_100000000000pl/notify',
            'method'  => 'post',
            'content' => [
                'emails'   => ['test@rzp.com'],
                'contacts' => ['9090908080']
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link is not active.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testExpirePaymentLinks' => [
        'request'  => [
            'url'     => '/payment_links/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testPaymentLinkMakePayment' => [
        'state' => [
            'times_paid'        => 1,
            'total_amount_paid' => 10100,
            'status'            => 'active',
            'status_reason'     => null,
        ],
    ],

    'testPaymentLinkCompletePayments' => [
        'state' => [
            'times_paid'        => 2,
            'total_amount_paid' => 20200,
            'status'            => 'inactive',
            'status_reason'     => 'completed',
        ],
    ],
];
