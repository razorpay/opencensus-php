<?php

use RZP\Models\Batch\Header;

return [

    'testBulkTerminalCreationValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'upi_terminal_onboarding',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::UPI_TERMINAL_ONBOARDING_MERCHANT_ID    => '10NodalAccount',
                        Header::UPI_TERMINAL_ONBOARDING_GATEWAY        => 'upi_juspay',
                        Header::UPI_TERMINAL_ONBOARDING_VPA            => 'umesh.rzp@abfspay',
                        Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID => 'parentMerchantId',
                        Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE  => 'parentChannelId',
                        Header::UPI_TERMINAL_ONBOARDING_EXPECTED             => 1,
                        Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE           => NULL,
                        Header::UPI_TERMINAL_ONBOARDING_RECURRING            => NULL,
                        Header::UPI_TERMINAL_ONBOARDING_MCC                  => NULL,
                        Header::UPI_TERMINAL_ONBOARDING_CATEGORY2            => NULL,
                        Header::UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE        => 'online',
                    ],
                ],
            ],
        ],
    ],

    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal_creation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'terminal_creation',
                'status'        => 'created',
                'total_count'   => 1,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testBulkUpiTerminalOnboardingCompletelyMigratedBatchUpload'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'upi_terminal_onboarding',
            ],
        ],
        'response' => [
            'content' => [
                'created_at'        =>  1590521524,
                'updated_at'        =>  1590521524,
                'id'                =>  'Be6Ob5J8kaMV6o',
                'entity_id'         =>  '100000Razorpay',
                'name'              =>  null,
                'batch_type_id'     =>  'upi_terminal_onboarding',
                'type'              =>  'upi_terminal_onboarding',
                'is_scheduled'      =>  false,
                'upload_count'      =>  0,
                'entity'            => 'batch',
                'failure_count'     =>  0,
                'total_count'       =>  1,
                'success_count'     =>  0,
                'attempts'          =>  0,
                'status'            =>  'created',
                'amount'            =>  0,
                'processed_amount'  =>  0,
            ],
        ],
        'expected_file_data'   =>  [
            [
                "Merchant Id",
                "Gateway",
                "Vpa",
                'Gateway Terminal ID2',
                'Gateway Access Code',
                'Expected',
                'Vpa Handle',
                'Recurring',
                'Mcc',
                'Category2',
                'Merchant Type'
            ],
            [
                "10NodalAccount",
                "upi_juspay",
                "umesh.rzp@abfspay",
                "parentMerchantId",
                "parentChannelId",
                '1',
                '',
                '',
                '',
                '',
                'online',
            ]
        ],
    ],

    'testBulkUpiTerminalOnboardingForBatchService' => [
        'request'  => [
            'url'     => '/upi_terminal_onboarding/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'idempotency_key'                              => 'randomIdempotencyKey',
                    Header::UPI_TERMINAL_ONBOARDING_MERCHANT_ID    => '10NodalAccount',
                    Header::UPI_TERMINAL_ONBOARDING_GATEWAY        => 'upi_juspay',
                    Header::UPI_TERMINAL_ONBOARDING_VPA            => 'umesh.rzp@abfspay',
                    Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID => 'parentMerchantId',
                    Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE  => 'parentChannelId',
                    Header::UPI_TERMINAL_ONBOARDING_EXPECTED             => 1,
                    Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE           => '',
                    Header::UPI_TERMINAL_ONBOARDING_RECURRING            => '',
                    Header::UPI_TERMINAL_ONBOARDING_MCC                  => '',
                    Header::UPI_TERMINAL_ONBOARDING_CATEGORY2            => '',
                    Header::UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE        => 'online',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'items' =>
                [
                    '0' => [
                        'idempotency_key'    => 'randomIdempotencyKey',
                        'success'           => true,
                        'http_status_code'  => 201,
                        'error'             =>  [
                            'code'        =>  '',
                            'description' =>  ''
                        ],
                        'terminal_id'           => 'ETbhgqkBRIiAkt',
                        'Merchant Id'           =>  '10NodalAccount',
                        'Gateway'               =>  'upi_juspay',
                        'Vpa'                   =>  'umesh.rzp@abfspay', // would be written in output file by batch file
                        'Gateway Terminal ID2'  =>  'parentMerchantId',
                        'Gateway Access Code'   =>  'parentChannelId',
                        'Expected'              =>  1,
                        'Vpa Handle'            =>  '',
                        'vpa_whitelisted'       => 'Y',
                    ]

                ]
            ]
        ],
    ],

];
