<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

return [
    'testCreateBvsValidationPoi' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'promoter_pan_name' => 'Test123',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCreateBvsValidationPoa' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => '%s',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateBvsValidationForGstin' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'gstin' => '07AADCB2230M1ZV',
            ],
        ],
        'response' => [
            'content' => [
                'gstin' => '07AADCB2230M1ZV',
            ],
        ],
    ],
];
