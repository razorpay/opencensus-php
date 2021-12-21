<?php

return [
    'testFOHEmailBulkWorkflow' => [
        'request' => [
            'content' => [
                'action'            => 'hold_funds',
                'risk_attributes'   => [
                    'trigger_communication' => '1',
                    'risk_tag'	            => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url' => '/merchants/10000000000000/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],
    'testFOHBulkWorkflowFdTicketCreate' => [
        'request' => [
            'content' => [
                'action'            => 'hold_funds',
                'risk_attributes'   => [
                    'trigger_communication' => '1',
                    'risk_tag'	            => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url' => '/merchants/10000000000000/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],
    'testSuspendEmailBulkWorkflow' => [
        'request' => [
            'content' => [
                'action'            => 'suspend',
                'risk_attributes'   => [
                    'trigger_communication' => '1',
                    'risk_tag'	            => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url' => '/merchants/10000000000000/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],
    'testDisableLiveEmailBulkWorkflow' => [
        'request' => [
            'content' => [
                'action'          => 'live_disable',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                      => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],

    'testDisableInternationalTemporaryEmailBulkWorkflow' => [
        'request'  => [
            'content' => [
                'action'          => 'disable_international',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                      => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],

    'testDisableInternationalPermanentEmailBulkWorkflow' => [
        'request'  => [
            'content' => [
                'action'          => 'disable_international',
                'risk_attributes' => [
                    'trigger_communication' => '2',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                      => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],

    'testInternationalDisableBulkWorkflowFdTicketCreate' => [
        'request'  => [
            'content' => [
                'action'          => 'disable_international',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                      => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],

    'testInternationalDisablePermanentBulkWorkflowFdTicketCreate' => [
        'request'  => [
            'content' => [
                'action'          => 'disable_international',
                'risk_attributes' => [
                    'trigger_communication' => '2',
                    'risk_tag'              => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                      => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],
];
