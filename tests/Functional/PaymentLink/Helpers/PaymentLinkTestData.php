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
                    'description' => 'expire_by should be at least 15 min ahead of the current time.',
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
                    'description' => 'expire_by should be at least 15 min ahead of the current time.',
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
        // Used to assert payment link's attributes after payment in test
        'payment_link' => [
            'times_paid'        => 1,
            'total_amount_paid' => 10100,
            'status'            => 'active',
            'status_reason'     => null,
        ],
    ],

    'testPaymentLinkCompletePayments' => [
        // Used to assert payment link's attributes after payment in test
        'payment_link_after_payment_1' => [
            'times_paid'        => 1,
            'total_amount_paid' => 10100,
            'status'            => 'active',
            'status_reason'     => null,
        ],
        'payment_link_after_payment_2' => [
            'times_paid'        => 2,
            'total_amount_paid' => 20200,
            'status'            => 'inactive',
            'status_reason'     => 'completed',
        ],
    ],

    'testDeactivatePaymentLink' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/deactivate',
            'method'  => 'patch',
            ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'status'        => 'inactive',
                'status_reason' => 'deactivated',
            ],
        ],
    ],

    'testDeactivateAlreadyDeactivatedPaymentLink' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/deactivate',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link cannot be deactivated as it is already inactive',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_LINK_ALREADY_INACTIVE,
        ],
    ],

    'testActivatePaymentLink' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/activate',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'status'        => 'active',
                'status_reason' => null,
            ],
        ],
    ],

    'testActivateLinkAlreadyActivated' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/activate',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment link cannot be activated as it is already active',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_LINK_ALREADY_ACTIVE,
        ],
    ],

    'testActivateWithTimesPayableLessThanTimesPaid' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/activate',
            'method'  => 'patch',
            'content' => [
                'times_payable' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Times payable cannot be less than the number of payments processed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMinExpiryTimeForActivation' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl/activate',
            'method'  => 'patch',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 min ahead of the current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditPaymentLinkToComplete' => [
        'request' => [
            'url'     => '/payment_links/pl_100000000000pl',
            'method'  => 'patch',
            'content' => [
                'times_payable' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'pl_100000000000pl',
                'times_payable' => 1,
                'status'        => 'inactive',
                'status_reason' => 'completed',
                'times_paid'    => 1
            ],
        ],
    ],
];
