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

    'testAadhaarDocumentUpload' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'aadhar_front',
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

    'testCreateBvsValidationForCin' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/verify/cin',
            'content' => [
                "company_cin" => "U65999KA2018PTC114468",
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],

    'testCreateBvsValidationForLLPIN' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/verify/cin',
            'content' => [
                "company_cin" => "AAP-9157",
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],
];
