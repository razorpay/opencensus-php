<?php

use RZP\Error\ErrorCode;

return [
    'testCreateRiskNoteWithPermission' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes',
            'method'  => 'post',
            'content' => [
                'note' => 'Test note #123456',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000001',
                'note'        => 'Test note #123456',
            ],
        ],
    ],
    'testCreateRiskNoteWithoutPermission' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes',
            'method'  => 'post',
            'content' => [
                'note' => 'Test note #123456',
            ]
        ],
        'response'  => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ]
    ],
    'testDeleteRiskNoteWithoutPermission' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/{id}',
            'method'  => 'delete',
            'content' => [
                'note' => 'Test note #123456',
            ]
        ],
        'response'  => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ]
    ],
    'testDeleteRiskNoteWithInvalidRiskId' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/121212',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],
    'testDeleteRiskNoteWithInvalidMerchant' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/risk_notes/{id}',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],
    'testDeleteRiskNoteWithValidMerchantRiskIdAndPermission' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/{id}',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ]
    ],
    'testGetAllRiskNotesWithNoDeletes' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testGetAllRiskNotesShowSoftDeletes' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/',
            'method'  => 'get',
            'content' => [
                'deleted' => '1'
            ]
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testGetAllRiskNotesHideSoftDeletes' => [
        'request'  => [
            'url'     => '/merchants/10000000000001/risk_notes/',
            'method'  => 'get',
            'content' => [
                'deleted' => '0'
            ]
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                ]
            ],
            'status_code' => 200,
        ],
    ]
];
