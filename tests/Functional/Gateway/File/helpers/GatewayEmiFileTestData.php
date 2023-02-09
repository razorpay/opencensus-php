<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;

return [
    'testGenerateEmiFile' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForIcici' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['icici'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForYesB' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['yesb'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'yesb',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithNoEmiPayments' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'sender'              => 'emifiles@razorpay.com',
                        'comments'            => 'No data present for gateway file processing in the given time period',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'error_code'          => 'error_generating_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'error_code'          => 'error_sending_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForIndusInd' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['indusind'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'indusind',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForIndusIndForCardMasking' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['indusind'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'indusind',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForKotak' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['kotak'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'kotak',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForRbl' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['rbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForHsbc' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['hsbc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'hsbc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForHsbcNoTransaction' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['hsbc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::today(Timezone::IST)->getTimestamp() + 1
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
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'hsbc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForCiti' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['citi'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForSbi' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['sbi'],
                'begin'   => Carbon::today(Timezone::IST)->subMinutes(30)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'sbi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForSbiNce' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['sbi_nce'],
                'begin'   => Carbon::today(Timezone::IST)->subMinutes(30)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'sbi_nce',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForSbiWithBeamFailure' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['sbi'],
                'begin'   => Carbon::today(Timezone::IST)->subMinutes(30)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items'  => [
                    [
                        'status'            => 'failed',
                        'attempts'          => 1,
                        'sender'            => 'emifiles@razorpay.com',
                        'type'              => 'emi',
                        'target'            => 'sbi',
                        'entity'            => 'gateway_file',
                        'error_code'        => 'error_sending_file',
                        'error_description' => 'Error occurred while sending file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForSbiWithNoSbiEmiTerminal' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['sbi'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'sbi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForSbiWithDuplicateSbiEmiTerminal' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['sbi'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'sbi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForScbl' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['scbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'scbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForBob' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['bob'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'bob',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForOneCard' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['onecard'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'onecard',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileForFederal' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['federal'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'federal',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],
];
