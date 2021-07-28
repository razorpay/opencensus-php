<?php

use RZP\Error\ErrorCode;

return [

    'testBatchServiceIsDown' => [
        'request'   => [
            'url'    => '/batches/batch_C7e2YqUIpZ2KwZ',
            'method' => 'get',
            'content'=> [
                'type'        => 'payment_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'id'        => 'batch_C7e2YqUIpZ2KwZ',
                'type'      => 'payment_link',
                'status'    => 'created',
            ],
        ],
    ],

    'testBatchServiceGetAllPaymentLinks' => [
        'request'   => [
            'url'    => '/batches',
            'method' => 'get',
            'content'=> [
                'type'        => 'payment_link',
                'with_config' => '1',
            ],
        ],
        'response'  => [
            'content'     => [
                'entity'        => 'collection',
                'count'         => 2,
                'items'         => [
                    [
                        'id'        => 'batch_00000000000002',
                        'type'      => 'payment_link',
                        'status'    => 'created',
                        'config'    => [
                            'sms_notify'    => '0',
                            'email_notify'  => '0',
                        ],
                    ],
                    [
                        'id'        => 'batch_00000000000001',
                        'type'      => 'payment_link',
                        'status'    => 'created',
                        'config'    => [
                            'sms_notify'    => '0',
                            'email_notify'  => '0',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testBatchServiceDownloadBatch' => [
        'request'   => [
            'url'    => '/batches/batch_C7e2YqUIpZ2KwZ/download',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'url'        => 'www.s3.download.com',
            ],
        ],
    ],

    'testBatchServiceIllegalDownloadBatch' => [
        'request'   => [
            'url'    => '/batches/batch_C7e2YqUIpZ2KwZ/download',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testBatchCreateToNewBatchService' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
                'name' => 'My batch entity',
                'draft'=> 0,
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'C3fzDCb4hA4F6b',
                'entity'           => 'batch',
                'batch_type_id'    => 'payment_link',
                'status'           => 'CREATED',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
            ],
        ],
    ],

    'testPayoutApprovalBatchCreate' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payout_approval',
                'name' => 'My Payout Approval',

                'otp'=> '0007',
                'token' => 'FLsaV87Zr0vvPt',
                'config' => ['user_comment' => 'some user comment']
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'payout_approval',
                'status'           => 'created',
                'name'             => 'My Payout Approval',
                'total_count'      => 1
            ],
        ],
    ],

    'testCreateLinkedAccountCreateBatch' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type'  => 'linked_account_create',
                'name'  => 'LA batch',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'linked_account_create',
                'name'          => 'LA batch',
                'status'        => 'created',
                'total_count'   => 2,
            ],
        ],
    ],

    'testCreatePaymentTransferBatch' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type'  => 'payment_transfer',
                'name'  => 'Transfer batch',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'payment_transfer',
                'name'          => 'Transfer batch',
                'status'        => 'created',
                'total_count'   => 2,
            ],
        ],
    ],

    'testCreateTransferReversalBatch' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type'  => 'transfer_reversal',
                'name'  => 'Reversal batch',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'transfer_reversal',
                'name'          => 'Reversal batch',
                'status'        => 'created',
                'total_count'   => 2,
            ],
        ],
    ],

    'testBatchRawAPIGetAllBatches' => [
        'request' => [
            'url'     => '/service/batch/batch',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'data'  => [
                    [
                        'created_at'       => 1557254046,
                        'updated_at'       => 1557254086,
                        'id'               => 'CSjjzIz2AGrISq',
                        'entity_id'        => 'BQXxEcUzUAP0Qr',
                        'name'             => 'kbkvk',
                        'batch_type_id'    => 'payment_link',
                        'mode'             => 'test',
                        'is_scheduled'     => false,
                        'upload_count'     => 0,
                        'processed_count'  => 11,
                        'failure_count'    => 0,
                        'total_count'      => 11,
                        'success_count'    => 11,
                        'attempts'         => 0,
                        'status'           => 'COMPLETED',
                        'settings'         => [
                            'draft' => '0',
                            'sms_notify' => '1',
                            'email_notify' => '0',
                        ],
                        'amount'           => 1155,
                        'processed_amount' => 1155,
                    ],
                    [
                        'created_at'       => 1557254046,
                        'updated_at'       => 1557254086,
                        'id'               => 'CSdhEZBIsG02UK',
                        'entity_id'        => 'BQXxEcUzUAP0Qr',
                        'name'             => 'kbkvk',
                        'batch_type_id'    => 'payment_link',
                        'mode'             => 'test',
                        'is_scheduled'     => false,
                        'upload_count'     => 0,
                        'processed_count'  => 11,
                        'failure_count'    => 0,
                        'total_count'      => 11,
                        'success_count'    => 11,
                        'attempts'         => 0,
                        'status'           => 'COMPLETED',
                        'settings'         => [
                            'name'     => 'pankaj',
                            'send_sms' => true
                        ],
                        'amount'           => 1155,
                        'processed_amount' => 1155,
                    ],
                ],
            ],
        ],
    ],

    'testBatchRawAPIUpdateSettings' => [
        'request' => [
            'url'     => '/service/batch/batch/CSZx0EmFsgAh8H/settings',
            'method'  => 'patch',
            'content' => [
                    'draft'=> 1,
                    'sms_notify'=> 0,
                    'email_notify'=> 0
            ],
        ],
        'response' => [
            'content' => [
                'created_at'=> 1557219569,
                'updated_at'=> 1557304243,
                'id'=> 'CSZx0EmFsgAh8H',
                'entity_id'=> 'BQXxEcUzUAP0Qr',
                'name'=> 'kbkvk',
                'batch_type_id'=> 'payment_link',
                'mode'=> 'test',
                'is_scheduled'=> false,
                'upload_count'=> 0,
                'processed_count'=> 11,
                'failure_count'=> 0,
                'total_count'=> 11,
                'success_count'=> 11,
                'attempts'=> 0,
                'status'=> 'COMPLETED',
                'settings'=> [
                    'draft'=> 1,
                    'sms_notify'=> 0,
                    'email_notify'=> 0
                ],
                'amount'=> 1155,
                'processed_amount'=> 1155
            ],
        ],
    ],

    'testBatchAdminFetchNoResult' => [
        'request'  => [
            'url'    => '/admin/batch.service?merchant_id=CWIYz6Yfu8tqZv',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => []
            ],
        ],
    ],

    'testBatchAPIGetAllBatchesWithFilters' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'get',
            'content'=> [
                'type'        => 'auth_link'
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "collection",

                "items"  => [
                    [
                        'id'               => 'batch_00000000000011',
                        'type'             => 'auth_link',
                        'total_count'      => 5,
                        'status'           => 'created',
                    ],
                    [
                        'id'               => 'batch_00000000000010',
                        'type'             => 'auth_link',
                        'total_count'      => 5,
                        'status'           => 'created',
                    ],
                ],
            ],
        ],
    ],

    'testBatchAPIGetBatchWithIdAndFilters' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'get',
            'content'=> [
                'type'        => 'auth_link',
                'id'          => 'batch_00000000000012'
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "items"   => [
                    [
                        'id'               => 'batch_00000000000012',
                        'type'             => 'auth_link',
                        'total_count'      => 6,
                        'status'           => 'created',
                    ]
                ],
            ],
        ],
    ],

    'testUserIdAndUserSetting' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '8392838584',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '8392838584',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],

    'testUserIdAndUserNotSetting' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '8392838584',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '8392838584',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],
];
