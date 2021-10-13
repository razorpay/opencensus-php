<?php

use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testPaymentPendingWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.pending',
        'contains' => [
            'payment',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'   => 'payment',
                    'amount'   => 50000,
                    'currency' => 'INR',
                    'status'   => 'pending',
                    'captured' => false,
                ],
            ],
        ],
    ],

    'testInitiatePaymentWithoutOrderShouldFail' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Cannot create Cash on delivery payment without corresponding order.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testInitiateCoDPaymentWithOrderInTerminalStatus' => [
        'response'  => [
            'content'     => [
                'error' => [
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID',
        ],
    ],
    'testCaptureCoDPaymentInNonPendingStatusShouldFail' => [
        'request'   => [
            'method'  => 'POST',
            'content' => [
                'amount' => 50000,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Only cash on delivery payments which are pending and not yet captured can be captured',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_PAYMENT_CAPTURE_ONLY_PENDING',
        ],
    ],

    'testPaymentTimeout' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [],
            'url'     => '/payments/timeout',
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testReminderCallbackForPendingPaymentBefore45Days' => [
        'request'   => [
            'method'  => 'POST',
            'content' => [],
            'url'     => '/reminders/send/test/payment/cod_payment_pending/randmPaymentId',
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200, // 200 is error code to reminder service to continue further callbacks on this schedule
        ],

    ],

    'testReminderCallbackStopScheduleTestData' => [
        'request'   => [
            'method'  => 'POST',
            'content' => [],
            'url'     => '/reminders/send/test/payment/cod_payment_pending/randmPaymentId',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 400, // 400 is error code to reminder service to stop further callbacks on this schedule
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_REMINDER_NOT_APPLICABLE',
        ],
    ],
];