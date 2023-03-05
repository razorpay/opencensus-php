<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testBulkStatus'   => [
        'request'   => [
            'url'     => '/fts/dashboard/fund_transfer_status/bulk',
            'method'  => 'post',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => '400',
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => 'BAD_REQUEST_ACCESS_DENIED',
        ],
    ],

    'testPartnerBankHealthDowntimeNotificationForDirectIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640430729,
                    'instrument'        => [
                        'bank'             => 'RBL',
                        'integration_type' => 'direct'
                    ],
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'IMPS',
                    'status'            => 'down',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank health webhook processed successfully',
            ],
        ],
    ],

    'testPartnerBankHealthUptimeNotificationForDirectIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640431729,
                    'instrument'        => [
                        'bank'             => 'RBL',
                        'integration_type' => 'direct'
                    ],
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'IMPS',
                    'status'            => 'up',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank health webhook processed successfully',
            ],
        ],
    ],

    'testDuplicatePartnerBankHealthNotificationsForDirectIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640430729,
                    'instrument'        => [
                        'bank'             => 'RBL',
                        'integration_type' => 'direct'
                    ],
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'IMPS',
                    'status'            => 'down',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'begin timestamp should be greater than that of the previous webhook'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPartnerBankHealthDowntimeNotificationForSharedIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640430729,
                    'instrument'        => [
                        'bank'             => 'YESBANK',
                        'integration_type' => 'shared'
                    ],
                    'include_merchants' => ["10000000000015"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'UPI',
                    'status'            => 'down',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank health webhook processed successfully',
            ],
        ],
    ],

    'testPartnerBankUptimeNotificationForSharedIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640432729,
                    'instrument'        => [
                        'bank'             => 'YESBANK',
                        'integration_type' => 'shared'
                    ],
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => ["10000000000013"],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'UPI',
                    'status'            => 'up',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank health webhook processed successfully',
            ],
        ],
    ],

    'testPartnerBankHealthDowntimeUptimeNotificationForSharedIntegration' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640434729,
                    'instrument'        => [
                        'bank'             => 'ICICI',
                        'integration_type' => 'shared'
                    ],
                    'include_merchants' => ["10000000000013"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'UPI',
                    'status'            => 'down',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank health webhook processed successfully',
            ],
        ],
    ],

    'testFetchPartnerBankHealthEntities' => [
        'request'  => [
            'url'     => '/admin/partner_bank_health?payout_mode=IMPS',
            'method'  => 'get',
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    0 => [
                        'id'         => 'pbh_downtime400000',
                        'event_type' => 'fail_fast_health.shared.imps',
                        'value'      => [
                            'ICIC'               => [
                                'last_down_at' => 1640431729,
                            ],
                            'YESB'               => [
                                'last_down_at' => 1640430729,
                            ],
                            'affected_merchants' => [
                                0 => 'ALL',
                            ],
                        ],
                    ],
                    1 => [
                        'id'         => 'pbh_downtime100000',
                        'event_type' => 'fail_fast_health.direct.imps',
                        'value'      => [
                            'ICIC'               => [
                                'last_down_at' => 1640431729,
                            ],
                            'YESB'               => [
                                'last_down_at' => 1640430729,
                            ],
                            'affected_merchants' => [
                                0 => 'ALL',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPartnerBankHealthUptimeNotificationWithoutCorrespondingDowntimeWebhook' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'begin'             => 1640430729,
                    'instrument'        => [
                        'bank'             => 'RBL',
                        'integration_type' => 'direct'
                    ],
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => [],
                    'source'            => 'fail_fast_health',
                    'mode'              => 'IMPS',
                    'status'            => 'up',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Uptime webhook received without a corresponding downtime webhook"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testPartnerBankDowntime' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'account_type'      => 'direct',
                    'include_merchants' => ["ALL"],
                    'exclude_merchants' => [],
                    'source'            => 'partner_bank_health',
                    'mode'              => 'IMPS',
                    'status'            => 'downtime',
                    'channel'           => 'RBL'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank downtime webhook processed successfully',
            ],
        ],
    ],
    'testPartnerBankUptime' => [
        'request'  => [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'partner_bank_health',
                'payload' => [
                    'account_type'      => 'direct',
                    'source'            => 'partner_bank_health',
                    'mode'              => 'IMPS',
                    'status'            => 'uptime',
                    'channel'           => 'RBL'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS partner bank downtime webhook processed successfully',
            ],
        ],
    ],
];
