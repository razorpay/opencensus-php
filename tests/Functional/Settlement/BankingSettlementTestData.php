<?php

return [

    'testGefuFileCreation' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileCreation' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => [
                'from_timestamp'=>'1689156000',
                'to_timestamp'=>'1689233400',
                'manual_gifu_time_range'=>[
                    'from_card_ds_timestamp'=>'1689093000',
                    'to_card_ds_timestamp'=>'1689186599',
                    'from_upi_ds_timestamp'=>'1689093000',
                    'to_upi_ds_timestamp'=>'1689182999',
                ],
                'send_file_to_beam'=> false
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileCreationAfterHoliday' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCustomGefuFileWithDsUpiTransactionsCreation' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileWithNonDsAndDsTransactionsCreation' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileCreationWithMultipleDsTransactionsScenario' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileCreationWithTiDbDelay' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testCustomGefuFileWithNonDsAndDsAndExcludingPosTransactions' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testGefuFileCreationWithGatewayTerminalIdPrefix190' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],

    'testGefuFileCreationWithoutPoolAccount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],
];
