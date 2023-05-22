<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'       => 'Sudden Downtime',
                'source'     => 'Partner Bank',
                'channel'    => 'yesbank',
                'mode'       => 'NEFT',
                'start_time' => '1590468916',
            ]
        ],
        'response' => [
            'content' => [
                'type'       => 'Sudden Downtime',
                'source'     => 'Partner Bank',
                'channel'    => 'yesbank',
                'mode'       => 'NEFT',
                'start_time' => '1590468916',
                'entity'     => 'fund_loading_downtimes',
                'admin'      => true,
            ]
        ],
    ],

    'testCreateDuplicateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'RBI',
                'channel'    => 'all',
                'mode'       => 'NEFT',
                'start_time' => 1632423321,
                'end_time'   => 1732423321,
            ]
        ],
        'response' => [
            'content' => [
                'type'    => 'Scheduled Maintenance Activity',
                'source'  => 'RBI',
                'channel' => 'all',
                'mode'    => 'NEFT',
                'entity'  => 'fund_loading_downtimes',
                'admin'   => true,
            ],
        ],
    ],

    'testCreateEntityEndTimeException' => [
        'request'   => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'       => 'Sudden Downtime',
                'source'     => 'Partner Bank',
                'channel'    => 'yesbank',
                'mode'       => 'NEFT',
                'start_time' => 1732333321,
                'end_time'   => 1732333021,
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
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityChannelException' => [
        'request'   => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => 'XYZ',
                'mode'       => 'NEFT',
                'start_time' => '1590468916',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid channel name: XYZ',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateEntityModeException' => [
        'request'   => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'RBI',
                'channel'    => 'all',
                'mode'       => 'RANDOM',
                'start_time' => '1590468916',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid mode name: RANDOM',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/100000downtime',
            'method'  => 'PATCH',
            'content' => [
                'start_time' => 1638952230,
                'end_time'   => 1738952230,
            ]
        ],
        'response' => [
            'content' => [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => 'all',
                'mode'       => 'NEFT',
                'start_time' => '1638952230',
                'end_time'   => '1738952230',
                'entity'     => 'fund_loading_downtimes',
                'admin'      => true,
            ],
        ],
    ],

    'testUpdateDuplicateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/100000downtime',
            'method'  => 'PATCH',
            'content' => [
                'end_time' => 1637940829,
                'mode'     => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => 'yesbank',
                'mode'       => 'NEFT',
                'start_time' => 1637930829,
                'end_time'   => 1637940829,
                'entity'     => 'fund_loading_downtimes',
                'admin'      => true,
            ],
        ],
    ],

    'testFetchById' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/fdown_100000downtime',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'type'             => 'Sudden Downtime',
                'source'           => 'RBI',
                'channel'          => 'all',
                'mode'             => 'NEFT',
                'created_by'       => 'chirag',
                'downtime_message' => 'All banks NEFT payments are down',
                'entity'           => 'fund_loading_downtimes',
                'admin'            => true,
            ],
        ],
    ],

    'testFetchAll' => [
        'request'  => [
            'url'     => '/fund_loading/downtimes',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'admin'  => true,
                'items'  => [
                    [
                        'type'             => 'Sudden Downtime',
                        'source'           => 'RBI',
                        'channel'          => 'all',
                        'mode'             => 'NEFT',
                        'created_by'       => 'chirag',
                        'downtime_message' => 'All banks NEFT payments are down',
                        'entity'           => 'fund_loading_downtimes',
                        'admin'            => true,
                    ],
                    [
                        'type'       => 'Scheduled Maintenance Activity',
                        'source'     => 'Partner Bank',
                        'channel'    => 'icicibank',
                        'mode'       => 'IMPS',
                        'created_by' => 'Chirag.Chiranjib',
                        'entity'     => "fund_loading_downtimes",
                        'admin'      => true,
                    ],
                ],
            ],
        ],
    ],

    'testDeleteEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/fdown_100000downtime',
            'method'  => 'DELETE',
            'content' => []
        ],
        'response' => [
            'content' => [
                'id'      => 'fdown_100000downtime',
                'deleted' => true
            ]
        ]
    ],

    'testFetchActiveDowntimesWithCurrentTimeAndParameters' => [
        'request'  => [
            'url'     => '/fund_loading/downtimes/active?channel=icicibank&source=Partner%20Bank',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 3,
                'admin'  => true,
                'items'  => [
                    [
                        'id'      => 'fdown_100003downtime',
                        'type'    => 'Scheduled Maintenance Activity',
                        'source'  => 'Partner Bank',
                        'channel' => 'icicibank',
                        'mode'    => 'RTGS',
                        'entity'  => 'fund_loading_downtimes',
                        'admin'   => true,
                    ],
                    [
                        'id'      => 'fdown_100001downtime',
                        'type'    => 'Scheduled Maintenance Activity',
                        'source'  => 'Partner Bank',
                        'channel' => 'icicibank',
                        'mode'    => 'UPI',
                        'entity'  => 'fund_loading_downtimes',
                        'admin'   => true,
                    ],
                    [
                        'id'      => 'fdown_100002downtime',
                        'type'    => 'Scheduled Maintenance Activity',
                        'source'  => 'Partner Bank',
                        'channel' => 'icicibank',
                        'mode'    => 'IMPS',
                        'entity'  => 'fund_loading_downtimes',
                        'admin'   => true,
                    ],
                ],
            ],
        ],
    ],

    'testFetchActiveDowntimesWithStartTimeAndParameters' => [
        'request'  => [
            'url'     => '/fund_loading/downtimes/active?mode=NEFT&channel=all',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items'  => [
                    [
                        'type'             => 'Sudden Downtime',
                        'source'           => 'RBI',
                        'channel'          => 'all',
                        'mode'             => 'NEFT',
                        'created_by'       => 'chirag',
                        'downtime_message' => 'All banks NEFT payments are down',
                        'entity'           => 'fund_loading_downtimes',
                        'admin'            => true,
                    ],
                ],
            ],
        ],
    ],

    'testFetchActiveDowntimesWithStartAndEndTimeAndParameters' => [
        'request'  => [
            'url'     => '/fund_loading/downtimes/active?created_by=Chirag',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items'  => [
                    [
                        'type'    => 'Sudden Downtime',
                        'source'  => 'RBI',
                        'channel' => 'all',
                        'mode'    => 'NEFT',
                        'entity'  => "fund_loading_downtimes",
                        'admin'   => true,
                    ],
                ],
            ],
        ],
    ],

    'testCreationFlow' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/creation',
            'method'  => 'POST',
            'content' => [
                "send_sms"        => "1",
                "send_email"      => "1",
                'downtime_inputs' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        [
                            'modes'      => ['NEFT', 'UPI'],
                            'start_time' => 1632313321, // Wednesday, 22 September 2021 17:52:01 GMT+05:30
                            'end_time'   => 1632314321, // Wednesday, 22 September 2021 18:08:41 GMT+05:30
                        ],
                        [
                            'modes'      => ['RTGS'],
                            'start_time' => 1632314321, // Wednesday, 22 September 2021 18:08:41 GMT+05:30
                            'end_time'   => 1632315321, // Wednesday, 22 September 2021 18:25:21 GMT+05:30
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'email'                => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632313321,
                            'end_time'   => 1632314321,
                            "modes"      => "NEFT,UPI",
                        ],
                        1 => [
                            'start_time' => 1632314321,
                            'end_time'   => 1632315321,
                            "modes"      => "RTGS",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreationFlowWithSMSV3Template' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/creation',
            'method'  => 'POST',
            'content' => [
                "send_sms"        => "1",
                "send_email"      => "1",
                'downtime_inputs' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        [
                            'modes'      => ['NEFT', 'UPI'],
                            'start_time' => 1632313321, // Wednesday, 22 September 2021 17:52:01 GMT+05:30
                            'end_time'   => 1632314321, // Wednesday, 22 September 2021 18:08:41 GMT+05:30
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'email'                => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632313321,
                            'end_time'   => 1632314321,
                            "modes"      => "NEFT,UPI",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testUpdationFlow' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/updation',
            'method'  => 'POST',
            'content' => [
                "send_sms"       => true,
                "send_email"     => true,
                'update_details' => [
                    [
                        'id'         => '100000downtime',
                        'start_time' => 1640802600, // Thursday, 30 December 2021 00:00:00 GMT+05:30
                        'end_time'   => 1640889000, // Friday, 31 December 2021 00:00:00 GMT+05:30
                    ],
                    [
                        'id'         => '100001downtime',
                        'start_time' => 1640802600, // Thursday, 30 December 2021 00:00:00 GMT+05:30
                        'end_time'   => 1640889000, // Friday, 31 December 2021 00:00:00 GMT+05:30

                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'email'                => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1640802600,
                            'end_time'   => 1640889000,
                            "modes"      => "IMPS,NEFT",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testUpdationFlowWithMultipleDurations' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/updation',
            'method'  => 'POST',
            'content' => [
                "send_sms"       => true,
                "send_email"     => true,
                'update_details' => [
                    [
                        'id'         => '100000downtime',
                        'start_time' => 1640802600, // Thursday, 30 December 2021 00:00:00 GMT+05:30
                        'end_time'   => 1640889000, // Friday, 31 December 2021 00:00:00 GMT+05:30
                    ],
                    [
                        'id'         => '100001downtime',
                        'start_time' => 1640806200, // Thursday, 30 December 2021 01:00:00 GMT+05:30
                        'end_time'   => 1640820600, // Thursday, 30 December 2021 05:00:00 GMT+05:30

                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'email'                => [
                    "successes" => 3,
                    "failures"  => 0,
                    "skipped"   => 1,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1640802600,
                            'end_time'   => 1640889000,
                            "modes"      => "IMPS",
                        ],
                        1 => [
                            'start_time' => 1640806200,
                            'end_time'   => 1640820600,
                            "modes"      => "NEFT",
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testResolutionFlow' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/resolution',
            'method'  => 'POST',
            'content' => [
                "send_sms"       => true,
                "send_email"     => true,
                'update_details' => [
                    [
                        'id'       => '100000downtime',
                        'end_time' => 1732313321,
                    ],
                    [
                        'id'       => '100001downtime',
                        'end_time' => 1732333321
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'email'                => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632413321,
                            'end_time'   => 1732313321,
                            "modes"      => "NEFT",
                        ],
                        1 => [
                            'start_time' => 1632413321,
                            'end_time'   => 1732333321,
                            "modes"      => "IMPS",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCancellationFlow' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/cancellation',
            'method'  => 'POST',
            'content' => [
                "send_sms"     => true,
                "send_email"   => true,
                'downtime_ids' => [
                    '100000downtime',
                    '100001downtime',
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'email'                => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632413321, // Sep 23, 2021 9:38 PM
                            'end_time'   => 1632443321, // Sep 24, 2021 5:58 AM
                            "modes"      => "NEFT,IMPS",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCancellationFlowWithNeitherSMSNorEmailNotifications' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/cancellation',
            'method'  => 'POST',
            'content' => [
                "send_sms"     => false,
                "send_email"   => false,
                'downtime_ids' => [
                    '100000downtime',
                    '100001downtime',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632413321,
                            'end_time'   => 1632443321,
                            "modes"      => "NEFT,IMPS",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCancellationFlowWithOnlySmsNotification' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/cancellation',
            'method'  => 'POST',
            'content' => [
                "send_sms"     => true,
                "send_email"   => false,
                'downtime_ids' => [
                    '100000downtime',
                    '100001downtime',
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'email'                => [
                    "successes" => 0,
                    "failures"  => 0,
                    "skipped"   => 0,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632413321,
                            'end_time'   => 1632443321,
                            "modes"      => "NEFT,IMPS",
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCancellationFlowWithOnlyEmailNotification' => [
        'request'  => [
            'url'     => '/fund_loading/downtime/notification/cancellation',
            'method'  => 'POST',
            'content' => [
                "send_sms"     => false,
                "send_email"   => true,
                'downtime_ids' => [
                    '100000downtime',
                    '100001downtime',
                ],
            ],
        ],
        'response' => [
            'content' => [
                "sms"                  => [
                    "successes" => 0,
                    "failures"  => 0,
                    "skipped"   => 0,
                ],
                'email'                => [
                    "successes" => 2,
                    "failures"  => 0,
                    "skipped"   => 2,
                ],
                'downtime_information' => [
                    'type'                => 'Scheduled Maintenance Activity',
                    'source'              => 'Partner Bank',
                    'channel'             => 'icicibank',
                    'durations_and_modes' => [
                        0 => [
                            'start_time' => 1632413321,
                            'end_time'   => 1632443321,
                            "modes"      => "NEFT,IMPS",
                        ],
                    ],
                ],
            ],
        ],
    ],
];
