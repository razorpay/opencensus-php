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
];