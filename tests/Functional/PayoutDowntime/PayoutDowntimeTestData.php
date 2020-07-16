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
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                    "entity"           => "payout_downtimes",
                    "admin"            => true,
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

    'testCreateEntityChannelException' => [
        'request'   => [
            'url'     => '/payouts/downtime/',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'           => 'Enabled',
                    'channel'          => 'RANDOM',
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
                    'created_by'       => 'OPS_A',
                    'start_time'       => '1590468916',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                    "entity"           => "payout_downtimes",
                    "admin"            => true,
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
                    'created_by'       => 'OPS_A',
                    'downtime_message' => 'HDFC bank NEFT payments are down',
                    'uptime_message'   => 'RBL is up',
                    "entity"           => "payout_downtimes",
                    "admin"            => true,
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
                        'created_by'       => 'OPS_A',
                        'downtime_message' => 'HDFC bank NEFT payments are down',
                        'entity'           => 'payout_downtimes',
                        'admin'            => true,
                    ],
                    [
                        'status'           => 'Enabled',
                        'channel'          => 'RBL',
                        'created_by'       => 'OPS_A',
                        'downtime_message' => 'HDFC bank NEFT payments are down',
                        'entity'           => 'payout_downtimes',
                        'admin'            => true,
                    ],
                ],
            ],
        ],
    ],

    'testEnabledDowntimeXDashboard' => [
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
                    'created_by'       => 'OPS_A',
                    'downtime_message' => 'RBL bank payments are down',
                ],
            ],
        ],
    ],

    'testSendEmailForCurrentAccount' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'RBL bank payments are down',
                    'enabled_email_option' => 'Yes',
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'RBL',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'RBL bank payments are down',
                    'enabled_email_status' => 'Processing'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testSendEmailForPoolAccount' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'Pool Network',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'Pool Network payments are down',
                    'enabled_email_option' => 'Yes',
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'Pool Network',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'Pool Network payments are down',
                    'enabled_email_status' => 'Processing'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testSendEmailForAll' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'All',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'All payments are down',
                    'enabled_email_option' => 'Yes',
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'All',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'All payments are down',
                    'enabled_email_status' => 'Processing'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testSendEmailForPrimaryMerchant' => [
        'request'  => [
            'url'     => '/payouts/downtime',
            'method'  => 'POST',
            'content' => [
                'payout_downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'All',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'All payments are down',
                    'enabled_email_option' => 'Yes',
                ],
            ]
        ],
        'response' => [
            'content'     => [
                'desc'     => 'Email step is initiated and will be sent shortly',
                'downtime' => [
                    'status'               => 'Enabled',
                    'channel'              => 'All',
                    'created_by'           => 'OPS_A',
                    'start_time'           => '1590468916',
                    'downtime_message'     => 'All payments are down',
                    'enabled_email_status' => 'Processing'
                ]
            ],
            'status_code' => 200,
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
                    'created_by'            => 'OPS_A',
                    'downtime_message'      => 'RBL payments are down',
                    'uptime_message'        => 'RBL payments are up',
                    'enabled_email_option'  => 'Yes',
                    'status'                => 'Disabled',
                    'disabled_email_option' => 'Yes',
                ],
            ],
            'status_code' => 200,
        ],
    ],

];
