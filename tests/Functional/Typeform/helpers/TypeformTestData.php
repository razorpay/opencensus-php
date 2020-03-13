<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return

    [
        'testFailureTypeformWebhookConsumptionSecurity' => [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                ],
            ],
            'response' => [
                'content'     => [
                    'error' => [
                        'code'        => ErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Access Denied'
                    ],
                ],
                'status_code' => 400,
            ],
        ],

        'testSuccessTypeformWebhookConsumptionSecurity' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    'event_id'      => '01E1BYRNFK9BD6YCH7PXRN0XV8',
                    'event_type'    => 'form_response',
                    'form_response' => [
                        'form_id'      => 'Xexe55',
                        'token'        => '01E1BYRNFK9BD6YCH7PXRN0XV8',
                        'landed_at'    => '2020-02-18T10:51:11Z',
                        'submitted_at' => '2020-02-18T10:51:11Z',
                        'hidden'       => [
                            'mid' => '100000',
                            'uid' => '1234',
                        ],
                    ],
                ],
            ],
            'response'    => [
                'content' => [
                    'authorization' => 'cleared'
                ],
            ],
            'status_code' => 200,
        ],

        'testInvalidDataTypeformWebhookConsumption' => [
            'request'   => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    'event_id'   => '01E1BYRNFK9BD6YCH7PXRN0XV8',
                    'event_type' => 'form_response',
                ],
            ],
            'response'  => [
                'content'     => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
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
