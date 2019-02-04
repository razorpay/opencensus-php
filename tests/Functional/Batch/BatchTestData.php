<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateBatchOfContactType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'contact',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'contact',
                'status'      => 'created',
                'total_count' => 3,
            ],
        ],
    ],

    'testCreateBatchOfContactTypeRequestFileEntries' => [
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => ' Jitendra',
            'Contact Email'        => 'jitendra@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => ' Mayur',
            'Contact Email'        => 'mayur@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => ' Jitendra',
            'Contact Email'        => 'jitendra@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
    ],
];
