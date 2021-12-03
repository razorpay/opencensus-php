<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'             => 'Sudden Downtime',
                'source'           => 'Partner Bank',
                'channel'          => 'yesbank',
                'mode'             => 'NEFT',
                'created_by'       => 'chirag',
                'start_time'       => '1590468916',
                'downtime_message' => 'YES Bank NEFT payments are down',
            ]
        ],
        'response' => [
            'content' => [
                'type'             => 'Sudden Downtime',
                'source'           => 'Partner Bank',
                'channel'          => 'yesbank',
                'mode'             => 'NEFT',
                'start_time'       => '1590468916',
                'created_by'       => 'chirag',
                'downtime_message' => 'YES Bank NEFT payments are down',
                'entity'           => 'fund_loading_downtimes',
                'admin'            => true,
            ]
        ],
    ],

    'testCreateDuplicateEntity' => [
        'request'  => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                'type'             => 'Scheduled Maintenance Activity',
                'source'           => 'RBI',
                'channel'          => 'all',
                'mode'             => 'NEFT',
                'start_time'       => 1632423321,
                'end_time'         => 1732423321,
                'created_by'       => 'chirag',
                'downtime_message' => 'New downtime message',
            ]
        ],
        'response' => [
            'content' => [
                'type'             => 'Scheduled Maintenance Activity',
                'source'           => 'RBI',
                'channel'          => 'all',
                'mode'             => 'NEFT',
                'created_by'       => 'chirag',
                'downtime_message' => 'All banks NEFT payments are down due to RBI',
                'entity'           => 'fund_loading_downtimes',
                'admin'            => true,
            ],
        ],
    ],

    'testCreateEntityEndTimeException' => [
        'request'   => [
            'url'     => '/fund_loading/downtime',
            'method'  => 'POST',
            'content' => [
                    'type'             => 'Sudden Downtime',
                    'source'           => 'Partner Bank',
                    'channel'          => 'yesbank',
                    'mode'             => 'NEFT',
                    'created_by'       => 'chirag',
                    'start_time'       => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
                    'end_time'         => Carbon::today(Timezone::IST)->getTimestamp(),
                    'downtime_message' => 'Bank is down',
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
                'type'             => 'Scheduled Maintenance Activity',
                'source'           => 'Partner Bank',
                'channel'          => 'XYZ',
                'mode'             => 'NEFT',
                'created_by'       => 'chirag',
                'start_time'       => '1590468916',
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
                'type'             => 'Scheduled Maintenance Activity',
                'source'           => 'RBI',
                'channel'          => 'all',
                'mode'             => 'RANDOM',
                'created_by'       => 'chirag',
                'start_time'       => '1590468916',
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
                'type'             => 'Sudden Downtime',
                'source'           => 'Partner Bank',
                'downtime_message' => 'All banks NEFT payments are down',
            ]
        ],
        'response' => [
            'content' => [
                    'type'             => 'Sudden Downtime',
                    'source'           => 'Partner Bank',
                    'channel'          => 'all',
                    'mode'             => 'NEFT',
                    'created_by'       => 'chirag',
                    'downtime_message' => 'All banks NEFT payments are down',
                    'entity'           => 'fund_loading_downtimes',
                    'admin'            => true,
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
                'type'             => 'Scheduled Maintenance Activity',
                'source'           => 'Partner Bank',
                'channel'          => 'yesbank',
                'mode'             => 'NEFT',
                'start_time'       => 1637930829,
                'end_time'         => 1637940829,
                'created_by'       => 'chirag',
                'downtime_message' => 'All banks NEFT payments are down due to RBI',
                'entity'           => 'fund_loading_downtimes',
                'admin'            => true,
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
                        'type'             => 'Scheduled Maintenance Activity',
                        'source'           => 'Partner Bank',
                        'channel'          => 'icicibank',
                        'mode'             => 'IMPS',
                        'created_by'       => 'Chirag.Chiranjib',
                        'entity'           => "fund_loading_downtimes",
                        'admin'            => true,
                    ],
                ],
            ],
        ],
    ],

    'testDeleteEntity' => [
        'request' => [
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
            'url'     => '/fund_loading/downtimes/active?created_by=chirag&channel=icicibank',
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
                        'type'             => 'Scheduled Maintenance Activity',
                        'source'           => 'Partner Bank',
                        'channel'          => 'icicibank',
                        'mode'             => 'UPI',
                        'created_by'       => 'chirag',
                        'entity'           => 'fund_loading_downtimes',
                        'admin'            => true,
                    ],
                    [
                        'type'             => 'Scheduled Maintenance Activity',
                        'source'           => 'Partner Bank',
                        'channel'          => 'icicibank',
                        'mode'             => 'IMPS',
                        'created_by'       => 'chirag',
                        'entity'           => 'fund_loading_downtimes',
                        'admin'            => true,
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
                'count'  => 2,
                'admin'  => true,
                'items'  => [
                    [
                        'type'             => 'Scheduled Maintenance Activity',
                        'source'           => 'Partner Bank',
                        'channel'          => 'icicibank',
                        'mode'             => 'IMPS',
                        'created_by'       => 'Chirag',
                        'entity'           => "fund_loading_downtimes",
                        'admin'            => true,
                    ],
                    [
                        'type'             => 'Sudden Downtime',
                        'source'           => 'RBI',
                        'channel'          => 'all',
                        'mode'             => 'NEFT',
                        'created_by'       => 'Chirag',
                        'downtime_message' => 'All banks NEFT payments are down',
                        'entity'           => 'fund_loading_downtimes',
                        'admin'            => true,
                    ],
                ],
            ],
        ],
    ]
];
