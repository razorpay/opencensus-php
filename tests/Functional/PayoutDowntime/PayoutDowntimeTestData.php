<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Models\PayoutDowntime\Constants;

return [
    'testCreateEntity' => [
        'request'  => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'desc'     => 'Email option is not selected',
                'downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ]
            ],
        ],
    ],

    'testCreateEntityEndTimeException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
                    'end_time'         => Carbon::today(Timezone::IST)->getTimestamp(),
                    'downtime_message' => 'Bank is down',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'End time should be greater than start time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityDownTimeException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => '',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The downtime message field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityStatusException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Disabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected status is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityModeException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'ABCD',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid mode provided: ABCD',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityChannelException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RANDOM',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid channel name: RANDOM',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditEntity' => [
        'request'  => [
            'url'     => '/payouts/downtime/',
            'method'  => 'patch',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'IMPS',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'desc'     => 'Email option is not selected',
                'downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'IMPS',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ]
            ],
        ],
    ],

    'testEditEntityStatusOnly' => [
        'request'  => [
            'url'     => '/payouts/downtime/',
            'method'  => 'patch',
            'content' => [
                'payout_downtime' => [
                    'status' => 'Disabled',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'desc'     => 'Email option is not selected',
                'downtime' => [
                    'status'           => 'Disabled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                    'uptime_message'   => 'RBL is up',
                ]
            ],
        ],
    ],

    'testEditEntityDisabledStateRequiredFieldsException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'patch',
            'content' => [
                'payout_downtime' => [
                    'status' => 'Disabled',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Please provide notification message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditEntityInvalidStatusException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'patch',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Cancelled',
                    'channel'          => 'RBL',
                    'mode'             => 'NEFT',
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid state change',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchById' => [
        'request'  => [
            'url'     => '/payouts/downtime/',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'status'           => 'Enabled',
                'channel'          => 'RBL',
                'mode'             => 'NEFT',
                'created_by'       => 'OPS_A',
                'downtime_message' => 'HDFC bank NEFT payments are down',
                'entity'           => 'payout_downtimes',
                'admin'            => true,
            ],
        ],
    ],

    'testFetchAll' => [
        'request'  => [
            'url'     => '/payouts/downtimes?count=2&skip=0',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'admin'  => true,
                'items'  => [
                    [
                        'status'           => 'Scheduled',
                        'channel'          => 'RBL',
                        'mode'             => 'NEFT',
                        'created_by'       => 'OPS_A',
                        'downtime_message' => 'HDFC bank NEFT payments are down',
                        'entity'           => 'payout_downtimes',
                        'admin'            => true,
                    ],
                    [
                        'status'           => 'Enabled',
                        'channel'          => 'RBL',
                        'mode'             => 'NEFT',
                        'created_by'       => 'OPS_A',
                        'downtime_message' => 'HDFC bank NEFT payments are down',
                        'entity'           => 'payout_downtimes',
                        'admin'            => true,
                    ],
                ],
            ],
        ],
    ],

    'testEnabledDowntime' => [
        'request'  => [
            'url'     => '/payouts/downtimes/enabled',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                [
                    'status'           => 'Enabled',
                    'channel'          => 'RBL',
                    'mode'             => 'IMPS',
                    'created_by'       => 'OPS_A',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                ],
            ],
        ],
    ],

    'testSendEmailEnabledState' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_option' => 'Yes',
                    Constants::MID_LIST    => [
                        '100abc000abc00'
                    ]
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_status' => 'Processing'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testSendEmailEnabledStateException' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_option' => 'Yes',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Please provide merchant ids',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendEmailInvalidMIDException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_option' => 'Yes',
                    Constants::MID_LIST    => [
                        '100abc000abc3'
                    ]
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => '100abc000abc3 is not a valid id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendEmailInvalidMIDException2' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_option' => 'Yes',
                    Constants::MID_LIST    => [
                        '100abc000abc00',
                        '100abc000abc35'
                    ]
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid merchant ids provided: 100abc000abc35',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendEmailDisabledState' => [
        'request'  => [
            'url'     => '/payouts/downtime/edit',
            'method'  => 'Patch',
            'content' => [
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'channel'               => 'RBL',
                    'mode'                  => 'NEFT',
                    'created_by'            => 'OPS_A',
                    'downtime_message'      => 'HDFC bank NEFT payments are down',
                    'uptime_message'        => 'HDFC bank NEFT payments are up',
                    'enabled_email_option'  => 'Yes',
                    'status'                => 'Disabled',
                    'disabled_email_option' => 'Yes',
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testSendEmailForPrimaryMerchant' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'mode'                 => 'NEFT',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'HDFC bank NEFT payments are down',
                    'enabled_email_option' => 'Yes',
                    Constants::MID_LIST    => [
                        '100abc000abc00'
                    ]
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Non banking merchant ids provided: 100abc000abc00',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

];
