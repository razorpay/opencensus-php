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
                'total_count' => 4,
            ],
        ],
    ],

    'testCreateBatchOfContactTypeRequestFileEntries' => [
        // Expected a new contact to be created.
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => 'Jitendra',
            'Contact Email'        => 'jitendra@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
        // Expected a new contact to be created.
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => ' Mayur',
            'Contact Email'        => 'mayur@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
        // Expected 1st ^^ contact to be used as has same details.
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => 'Jitendra',
            'Contact Email'        => 'jitendra@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
        // Expected a new contact to be created, though a contact exists with following details but is inactive.
        [
            'Contact Type'         => 'vendor',
            'Contact Name'         => 'Another Example',
            'Contact Email'        => 'another@example.com',
            'Contact Mobile'       => '9988998899',
            'Contact Reference Id' => '',
            'notes[\'place\']'     => 'Bangalore',
            'notes[\'code\']'      => 'ABC-123',
        ],
    ],
];
