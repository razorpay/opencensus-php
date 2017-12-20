<?php

return [
   'testUpiFailedRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['upi_icici'],
            ],
            'url' => '/gateway/files',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund_failed',
                        'target'              => 'upi_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],

    'testNoFailedRefunds' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['upi_icici'],
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'comments'            => 'No data present for gateway file processing in the given time period',
                        'type'                => 'refund_failed',
                        'target'              => 'upi_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],
];
