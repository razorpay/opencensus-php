<?php

return [
    // This is used in various other tests as well
    'testCreatePayoutsBatchWithoutIdemKey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_batch',
            'content' => [
                'reference_id' => 'whu2i2830923ieni',
                'payouts'      => [
                    [
                        'account_number'       => '2224440041626905',
                        'amount'               => 1000,
                        'currency'             => 'INR',
                        'mode'                 => 'NEFT',
                        'purpose'              => 'payout',
                        'reference_id'         => 'MFN1234',
                        'queue_if_low_balance' => false,
                        'fund_account'         => [
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'name'           => 'Gaurav Kumar',
                                'ifsc'           => 'HDFC0001234',
                                'account_number' => '1121431121541121',
                            ],
                            'contact'      => [
                                'name'         => 'Gaurav Kumar',
                                'email'        => 'gaurav.kumar@example.com',
                                'contact'      => '9876543210',
                                'type'         => 'vendor',
                                'reference_id' => 'Acme Contact ID 12345',
                                'notes'        => [
                                    'notes_key_1' => 'Tea, Earl Grey, Hot',
                                    'notes_key_2' => 'Tea, Earl Grey... decaf.',
                                ],
                            ],
                        ],
                        'narration'            => 'Acme Corp Fund Transfer',
                    ],
                    [
                        'account_number'       => '2224440041626905',
                        'amount'               => 1000,
                        'currency'             => 'INR',
                        'mode'                 => 'IMPS',
                        'purpose'              => 'payout',
                        'reference_id'         => 'Acme Transaction ID 12345',
                        'queue_if_low_balance' => false,
                        'fund_account'         => [
                            'account_type' => 'bank_account',
                            'bank_account' => [
                                'name'           => 'Gaurav Kumar',
                                'ifsc'           => 'HDFC0001234',
                                'account_number' => '1121431121541121',
                            ],
                            'contact'      => [
                                'name'         => 'Gaurav Kumar',
                                'email'        => 'gaurav.kumar@example.com',
                                'contact'      => '9999999999',
                                'type'         => 'vendor',
                                'reference_id' => 'Acme Contact ID 12345',
                                'notes'        => [
                                    'notes_key_1' => 'Tea, Earl Grey, Hot',
                                    'notes_key_2' => 'Tea, Earl Grey... decaf.',
                                ],
                            ],
                        ],
                        'narration'            => 'Acme Corp Fund Transfer',
                    ],
                    [
                        'account_number'       => '2224440041626905',
                        'amount'               => 1000,
                        'currency'             => 'INR',
                        'mode'                 => 'NEFT',
                        'purpose'              => 'payout',
                        'reference_id'         => 'MFN12345',
                        'queue_if_low_balance' => false,
                        'fund_account_id'      => 'fa_TheTestFundAcc',
                        'narration'            => 'Acme Corp Fund Transfer',
                    ],
                    [
                        'account_number'       => '2224440041626905',
                        'amount'               => 1000,
                        'currency'             => 'INR',
                        'mode'                 => 'amazonpay',
                        'purpose'              => 'refund',
                        'fund_account'         => [
                            'account_type' => 'wallet',
                            'wallet'       => [
                                'provider' => 'amazonpay',
                                'phone'    => '+919876543210',
                                'email'    => ' gaurav.kumar@example.com',
                                'name'     => 'Gaurav Kumar',
                            ],
                            'contact'      => [
                                'name'         => 'Gaurav Kumar',
                                'email'        => 'gaurav.kumar@example.com',
                                'contact'      => '9876543210',
                                'type'         => 'employee',
                                'reference_id' => 'Acme Contact ID 12345',
                                'notes'        => [
                                    'notes_key_1' => 'Tea, Earl Grey, Hot',
                                    'notes_key_2' => 'Tea, Earl Grey… decaf.',
                                ],
                            ],
                        ],
                        'queue_if_low_balance' => true,
                        'reference_id'         => 'Acme Transaction ID 12345',
                        'narration'            => 'Acme Corp Fund Transfer',
                        'notes'                => [
                            'notes_key_1' => 'Beam me up Scotty',
                            'notes_key_2' => 'Engage',
                        ],
                    ],
                    [
                        'account_number'       => '2224440041626905',
                        'amount'               => 1000,
                        'currency'             => 'INR',
                        'mode'                 => 'UPI',
                        'purpose'              => 'refund',
                        'fund_account'         => [
                            'account_type' => 'vpa',
                            'vpa'          => [
                                'address' => 'gauravkumar@exampleupi',
                            ],
                            'contact'      => [
                                'name'         => 'Gaurav Kumar',
                                'email'        => 'gaurav.kumar@example.com',
                                'contact'      => '9876543210',
                                'type'         => 'self',
                                'reference_id' => 'Acme Contact ID 12345',
                                'notes'        => [
                                    'notes_key_1' => 'Tea, Earl Grey, Hot',
                                    'notes_key_2' => 'Tea, Earl Grey… decaf.',
                                ],
                            ],
                        ],
                        'queue_if_low_balance' => true,
                        'reference_id'         => 'Acme Transaction ID 12345',
                        'narration'            => 'Acme Corp Fund Transfer',
                        'notes'                => [
                            'notes_key_1' => 'Beam me up Scotty',
                            'notes_key_2' => 'Engage',
                        ],
                    ],
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity'       => 'payouts.batch',
                'reference_id' => 'whu2i2830923ieni',
                'status'       => 'Accepted',
            ],
        ],
    ],

    'testPayoutWebhooks' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk',
            'content' => [
                [
                    'razorpayx_account_number' => '2224440041626905',
                    'payout'                   => [
                        'amount'       => '1000',
                        'currency'     => 'INR',
                        'mode'         => 'NEFT',
                        'purpose'      => 'payment',
                        'narration'    => 'Acme Corp Fund Transfer',
                        'reference_id' => 'MFN1234'
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Gaurav Kumar',
                        'account_IFSC'   => 'HDFC0001234',
                        'account_number' => '1121431121541121',
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Gaurav Kumar',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'notes'                    => [
                        'batch_reference_id' => 'whu2i2830923ieni',
                    ],
                    'idempotency_key'          => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number' => '2224440041626905',
                    'payout'                   => [
                        'amount'       => '1000',
                        'currency'     => 'INR',
                        'mode'         => 'IMPS',
                        'purpose'      => 'payout',
                        'narration'    => 'Acme Corp Fund Transfer',
                        'reference_id' => 'MFN1234'
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Gaurav Kumar',
                        'account_IFSC'   => 'HDFC0001234',
                        'account_number' => '1121431121541121',
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Gaurav Kumar',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'notes'                    => [
                        'batch_reference_id' => 'whu2i2830923ieni',
                    ],
                    'idempotency_key'          => 'batch_abc1234'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'batch_id'         => 'C3fzDCb4hA4F6b',
                        'idempotency_key'  => 'batch_abc123',
                        'error'            => [
                            'description' => 'Invalid purpose: payment',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400,
                    ],
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnPayoutCreationFailure' => [
        'entity'   => 'event',
        'event'    => 'payout.creation.failed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'id'              => '',
                    'entity'          => 'payout',
                    'fund_account_id' => '',
                    'amount'          => '1000',
                    'currency'        => 'INR',
                    'notes'           => [
                        'batch_reference_id' => 'whu2i2830923ieni',
                        'correlation_id'     => '67d30314-f9b7-11eb-ab60-acde48001122',
                    ],
                    'status'          => 'failed',
                    'purpose'         => 'payment',
                    'mode'            => 'NEFT',
                    'reference_id'    => 'MFN1234',
                    'narration'       => 'Acme Corp Fund Transfer',
                    'batch_id'        => 'batch_C3fzDCb4hA4F6b',
                    'failure_reason'  => 'Invalid purpose: payment',
                    'fund_account'    =>
                        [
                            'bank_account' =>
                                [
                                    'name'           => 'Gaurav Kumar',
                                    'account_number' => '1121431121541121'
                                ],
                        ],
                    'account_number'  => '2224440041626905',
                    'batch_status'    => 'processed',
                    'error'           => [
                        'description' => 'Invalid purpose: payment',
                        'source'      => 'business',
                        'reason'      => 'BAD_REQUEST_ERROR',
                    ],
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnPayoutCreation' => [
        'entity'   => 'event',
        'event'    => 'payout.initiated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'amount'         => 1000,
                    'currency'       => 'INR',
                    'notes'          => [
                        'batch_reference_id' => 'whu2i2830923ieni',
                        'correlation_id'     => '67d30314-f9b7-11eb-ab60-acde48001122',
                    ],
                    'status'         => 'processing',
                    'purpose'        => 'payout',
                    'mode'           => 'IMPS',
                    'reference_id'   => 'MFN1234',
                    'narration'      => 'Acme Corp Fund Transfer',
                    'batch_id'       => 'batch_C3fzDCb4hA4F6b',
                    'fund_account'   =>
                        [
                            'bank_account' =>
                                [
                                    'name'           => 'Gaurav Kumar',
                                    'account_number' => '1121431121541121'
                                ],
                        ],
                    'account_number' => '2224440041626905',
                    'batch_status'   => 'processed',
                ],
            ],
        ],
    ],

    'testBatchesCreateWithProxyAuthWhenOtpIsSent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/batches',
            'content' => [
                'type'    => 'payout',
                'name'    => 'RandomFileName',
                'file_id' => 'file_ARandomFileIds',
                'otp'     => '0007',
                'token'   => 'RandomTokenRzp',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'batch',
                'type'   => 'payout',
                'status' => 'created',
            ],
        ],
    ],

    'testPayoutWebhooksForDashboardBasedBulkPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk',
            'content' => [
                [
                    'razorpayx_account_number' => '2224440041626905',
                    'payout'                   => [
                        'amount'       => '1000',
                        'currency'     => 'INR',
                        'mode'         => 'IMPS',
                        'purpose'      => 'payout',
                        'narration'    => 'Acme Corp Fund Transfer',
                        'reference_id' => 'MFN1234'
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Gaurav Kumar',
                        'account_IFSC'   => 'HDFC0001234',
                        'account_number' => '1121431121541121',
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Gaurav Kumar',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'idempotency_key'          => 'batch_abc123'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'batch_id'        => 'batch_C3fzDCb4hA4F6b',
                        'idempotency_key' => 'batch_abc123'
                    ],
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnPayoutCreationForDashboardBasedBulkPayouts' => [
        'entity'   => 'event',
        'event'    => 'payout.initiated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'amount'         => 1000,
                    'currency'       => 'INR',
                    'status'         => 'processing',
                    'purpose'        => 'payout',
                    'mode'           => 'IMPS',
                    'reference_id'   => 'MFN1234',
                    'narration'      => 'Acme Corp Fund Transfer',
                    'batch_id'       => 'batch_C3fzDCb4hA4F6b',
                    'notes'          => [],
                ],
            ],
        ],
    ],

    'testCreatePayoutBatchesXDemoCron' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_batch_x_demo_cron',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payouts.batch',
                'status' => 'Accepted'
            ],
        ],
    ]
];
