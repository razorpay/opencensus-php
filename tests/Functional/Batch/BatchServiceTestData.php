<?php

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

];
