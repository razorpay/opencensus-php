<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

use RZP\Tests\Functional\Fixtures\Entity\Iin;
return [
    'testAddIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
        'response' => [
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
    ],

    'testEditIin' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'put',
            'content' => [
                'country' => 'IN',
                'issuer' => 'HDFC',
                'issuer_name' => 'HDFC',
                'emi' => 1,
                'network' => 'RuPay',
                'type' => 'credit'
            ],
        ],
        'response' => [
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'credit',
                'country' => 'IN',
                'issuer' => 'HDFC',
                'issuer_name' => 'HDFC',
                'emi' => true,
            ],
        ],
    ],


    'testGetIin' => [
        'request' => [
            'url' => '/iins/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => 607500,
                'category'      => 'STANDARD',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => 'PUNJAB NATIONAL BANK',
                'trivia'        => 'random trivia'
            ]
        ],
    ],

    'testGetIins' => [
        'request' => [
            'url' => '/iins',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 19,
                'items' => [
                    [
                    ]
                ]
            ]
        ],
    ],

    'testImportIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'files' => [
                'file' => '',
            ],
            'content' => [
                'network' => 'MasterCard',
            ],
        ],
        'response' => [
            'content' => [
                'duplicates' => [],
                'db_conflicts' => [],
                'network_errors' => [
                    '497522' => [
                        8,
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],

    'testImportIinWithIssuer' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'files' => [
                'file' => '',
            ],
            'content' => [
                'network' => 'MasterCard',
            ],
        ],
        'response' => [
            'content' => [
                'duplicates'  => [
                ],
                'db_conflicts' => [
                ],
                'network_errors' => [
                    '497522' => [
                        8,
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],

    'testIinRangeUploadWithType' => [
        'request' => [
            'url' => '/iins/range/upload',
            'method' => 'post',
            'content' => [
                'min' => 652850,
                'max' => 652855,
                'network' => 'RuPay',
                'type' => 'credit',
                'country' => 'IN'
            ]
        ],
        'response' => [
            'content' => [
                'success' => 6,
            ],
        ],
    ],
];
