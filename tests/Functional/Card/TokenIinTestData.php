<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateIin' => [
        'request' => [
            'url' => '/tokens/iin',
            'method' => 'post',
            'content' => [
                'iin'          => "112333",
                'high_range'   => '9999999',
                'low_range'    => '1111111',
            ],
        ],
        'response' => [
            'content' => [
                'iin'          => '112333',
                'high_range'   => '9999999',
               'low_range'    => '1111111',
            ],
        ],
    ],

    'testUpdateIin' => [
        'request' => [
            'url' => '/tokens/iin/update/112333',
            'method' => 'post',
            'content' => [
                'high_range'        => '99999',
                'low_range'         => '11111',
            ],
        ],
        'response' => [
            'content' => [
                'iin'             => '112333',
                'high_range'   => '99999',
                'low_range'    => '11111',
            ],
        ],
    ],

    'testfetchIin' => [
        'request' => [
            'url' => '/tokens/iin/fetch/112333',
            'method' => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'iin'  => '112333',
                'high_range'   => '9999999',
                'low_range'    => '1111111',
            ],
        ],
    ],

    'testfetchbyTokenIin' => [
        'request' => [
            'url' => '/tokens/iin/112333',
            'method' => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'iin'  => '112333',
                'high_range'   => '9999999',
                'low_range'    => '1111111',
            ],
        ],
    ],

];
