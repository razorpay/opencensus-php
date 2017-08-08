<?php

use Carbon\Carbon;

return [
    'testRefundFileProcessor' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessorWithCustomRecipients' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'gateway'    => 'netbanking_hdfc',
                'bank'       => 'HDFC',
                'recipients' => ['test@razorpay.com'],
                'from'       => Carbon::today('Asia/Kolkata')->timestamp,
                'to'         => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'recipients'          => ['test@razorpay.com'],
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessorWithNoRefundData' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'no_data_for_file_generation',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessorWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'error_creating_file',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessingWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'error_sending_mail',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileFileGenErrorRetryProcessing' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileMailSendErrorRetryProcessing' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ]
];
