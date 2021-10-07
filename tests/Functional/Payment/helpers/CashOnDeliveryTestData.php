<?php

use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

return [
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
];