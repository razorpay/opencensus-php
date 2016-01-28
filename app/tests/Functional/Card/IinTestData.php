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

    'testEditIin' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'post',
            'content' => [
                'country' => 'IN',
                'issuer' => 'HDFC',
                'issuer_name' => 'HDFC',
                'emi' => 0
            ],
        ],
        'response' => [
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'debit',
                'country' => 'IN',
                'issuer' => 'HDFC',
                'issuer_name' => 'HDFC',
                'emi' => '0'
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
                'count' => 7,
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
                'duplicates'  => [
                    '513456' => [
                        [
                            'iin' =>'513456',
                            'category' => 'CLASSIC',
                            'network' => 'MasterCard',
                            'type' => 'debit',
                            'country' => 'IN',
                        ],
                    ],
                    '511666' => [
                        [
                            'iin' => '511666',
                            'category' => 'PREMIUM',
                            'network' => 'MasterCard',
                            'type' => 'debit',
                            'country' => 'IN',
                        ]
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
                        ],
                        'file_entry' => [
                            'iin'       => '549752',
                            'category'  => 'STANDARD',
                            'network'   => 'MasterCard',
                            'type'      => 'credit',
                            'country'   => 'IN',
                        ],
                    ],
                ],
                'network_errors' => [
                    '497522' => [
                        [
                            'iin'       => '497522',
                            'category'  => 'CLASSIC',
                            'network'   => 'Visa',
                            'type'      => 'credit',
                            'country'   => 'IN',
                        ],
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],
];
