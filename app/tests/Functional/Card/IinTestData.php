<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

use Tests\Functional\Fixtures\Entity\Iin;
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

    'testGetIin' => [
        'request' => [
            'url' => '/iins/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'       => 607500,
                'category'  => 'STANDARD',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'country'   => 'IN',
                'issuer'    => 'PUNJAB NATIONAL BANK',
                'trivia'    => 'random trivia'
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
                'count' => 6,
                'items' => [
                    [
                    ]
                ]
            ]
        ],
    ],

    'testImportIin' => [
        'request' => [
            'url' => '/iins/import',
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
                    '513456' => [
                        [
                            'iin' =>'513456',
                            'category' => 'PREMIUM',
                            'network' => 'MasterCard',
                            'type' => 'debit',
                            'country' => 'IN',
                            'issuer' => null
                        ],
                        [
                            'iin' =>'513456',
                            'category' => 'CLASSIC',
                            'network' => 'MasterCard',
                            'type' => 'debit',
                            'country' => 'IN',
                            'issuer' => null
                        ],
                    ]
                ],
                'db_conflicts'=> [
                    '549752' => [
                        'db_entry' => [
                            'iin'       => 549752,
                            'category'  => 'STANDARD',
                            'network'   => 'MasterCard',
                            'type'      => 'credit',
                            'country'   => 'IN',
                            'issuer'    => 'PUNJAB NATIONAL BANK',
                            'trivia'    => 'random trivia'
                        ],
                        'file_entry' => [
                            'iin'       => '549752',
                            'category'  => 'STANDARD',
                            'network'   => 'MasterCard',
                            'type'      => 'credit',
                            'country'   => 'IN',
                            'issuer'    => null,
                        ],
                    ],
                ],
            ]
        ],
    ],
];
