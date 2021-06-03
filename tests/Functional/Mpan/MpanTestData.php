<?php


use RZP\Error\ErrorCode;

return [
    'testMpanIssue' =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Visa',
                'count'   => 10,
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 10,
                'items'     => [
                ],
            ],
        ],
    ],

    'testMpanIssueCountExceedsAllowedLimit'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'MasterCard',
                'count'   => 50000,
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMpanIssueInvalidNetwork'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Amex',
                'count'   => 4,
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMpanIssueRequestedCountUnavailable'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Visa',
                'count'   => 15, //we have added only 10 visa mpans into table during test setup
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\ServerErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_REQUESTED_MPANS_NOT_AVAILABLE,
        ],
    ],

    'testMpanBulk' => [
        'request' => [
            'url'     => '/mpans/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => '90c163422f1226800ad7c82907754550d93f5d73709a512e94365d5e1331d7ed',
                    'MPAN'            => '112c7f95466cb1fd1aad8852b0a93d2ced7d6267280b92fd13623578e9721ada',
                    'RPAN'            => 'e2ed0d097fafacf6ab293a85b0f57441519e70e6636f03745496ba72ff8c6aab',
                ],
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => 'c152737faf6ca4d6273cc5cbeee505c26bb18065da30c6908aac920f7b7d107c',
                    'MPAN'            => '278d9446a09ca9e866965ab30ce0b844aa2d8ab3b5de609d96613d495fc6a2d4',
                    'RPAN'            => '2d8c1ac2925a87c4a765f2f88a019e27e20fb77249f879bd89b5747209c2e9bd',
                ],
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => 'e57de225cd5e9a43ee7d2748aea44996463da7a75618463a6566dcaae825dc0a',
                    'MPAN'            => '890cf4a0407b4fa036942b5e0841bd48d4ed10604642a38f8de1567ca45e8f51',
                    'RPAN'            => 'e6d1e114a34d877f67886bb40c261560bb2ada805671088a0c0850b7c9908ad6',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => true,
                        'error'           => ['description' => '', 'code' => ''],
                        'VPAN'            => '460490******8589', // unmasked value = 4604901012748589
                        'MPAN'            => '512260******8579', // unmasked value = 5122600012748579
                        'RPAN'            => '610002******8582', // unmasked value = 6100020012748582
                    ],
                    '1' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => true,
                        'error'           => ['description' => '', 'code' => ''],
                        'VPAN'            => '460490******8588', // unmasked value = 4604901012748588
                        'MPAN'            => '512260******8578', // unmasked value = 5122600012748578
                        'RPAN'            => '610002******8581', // unmasked value = 6100020012748581
                    ],
                    '2' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => true,
                        'error'           => ['description' => '', 'code' => ''],
                        'VPAN'            => '460490******8586', // unmasked value = 4604901012748586
                        'MPAN'            => '512260******8574', // unmasked value = 5122600012748574
                        'RPAN'            => '610002******8585', // unmasked value = 6100020012748585
                    ],
                ]
            ],
        ],
        'unmasked_mpans' => [
            [
                'VPAN'            => '4604901012748589',
                'MPAN'            => '5122600012748579',
                'RPAN'            => '6100020012748582',
            ],
            [
                'VPAN'            => '4604901012748588',
                'MPAN'            => '5122600012748578',
                'RPAN'            => '6100020012748581',
            ],
            [
                'VPAN'            => '4604901012748586',
                'MPAN'            => '5122600012748574',
                'RPAN'            => '6100020012748585',
            ],
        ]
    ],

    'testMpanBulkInvalidMpan' => [
        'request' => [
            'url'     => '/mpans/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => '90c163422f1226800ad7c82907754550d93f5d73709a512e94365d5e1331d7ed', // decrypted value = 4604901012748589
                    'MPAN'            => '112c7f95466cb1fd1aad8852b0a93d2ced7d6267280b92fd13623578e9721ada', // decrypted value = 5122600012748579
                    'RPAN'            => 'e2ed0d097fafacf6ab293a85b0f57441519e70e6636f03745496ba72ff8c6aab', // decrypted value = 6100020012748582
                ],
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => 'f6cba52bc8415a417ea04837ed45b5fc', // decrypted value = 123
                    'MPAN'            => '278d9446a09ca9e866965ab30ce0b844aa2d8ab3b5de609d96613d495fc6a2d4', // decrypted value = 5122600012748578
                    'RPAN'            => '2d8c1ac2925a87c4a765f2f88a019e27e20fb77249f879bd89b5747209c2e9bd', // decrypted value = 6100020012748581
                ],
                [
                    'idempotency_key' => 'myIdempotencyKey',
                    'VPAN'            => 'fec4acfbd152e9ba67bc27dd1c33fcaec7673c2ad7ab4994d08e837a0edd100c', // decrypted value = 4604901005005799,     this mpan is seeded in test Setup
                    'MPAN'            => '278d9446a09ca9e866965ab30ce0b844aa2d8ab3b5de609d96613d495fc6a2d4', // decrypted value = 5122600012748578
                    'RPAN'            => '2d8c1ac2925a87c4a765f2f88a019e27e20fb77249f879bd89b5747209c2e9bd', // decrypted value = 6100020012748581
                ],
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => true,
                        'error'           => ['description' => '', 'code' => ''],
                        'VPAN'            => '460490******8589',
                        'MPAN'            => '512260******8579',
                        'RPAN'            => '610002******8582',
                    ],
                    '1' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => false,
                        'error'           => ['description' => 'The mpan must be 16 digits.', 'code' => 'SERVER_ERROR'],
                        'VPAN'            => '123',
                        'MPAN'            => '512260******8578',
                        'RPAN'            => '610002******8581',
                    ],
                    '2' => [
                        'idempotency_key' => 'myIdempotencyKey',
                        'success'         => false,
                        'VPAN'            => '460490******5799',
                        'MPAN'            => '512260******8578',
                        'RPAN'            => '610002******8581',
                    ],
                ]
            ],
        ],
    ],

    'testMpanTokenizeExistingMpans' => [
        'request' => [
            'url'     => '/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  3,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count'=>  3,
                'tokenization_failed_count' =>  0,
            ]
        ]
    ],

    'testMpanTokenizeExistingMpansInputValidationFailure' => [
        'request' => [
            'url'     => '/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  -3,
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMpanTokenizeExistingMpansOneCardVaultRequestFails' => [
        'request' => [
            'url'     => '/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  3,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count'=>  2,
                'tokenization_failed_count' =>  1,
            ]
        ]
    ],

    'testQrCodeTokenizeExistingMpans' => [
        'request' => [
            'url'     => '/qr_code/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'qr_string_mpan_tokenization_success_count' =>  1,
                'qr_string_mpan_tokenization_failed_count'  =>  0,
            ]
        ]
    ],

    'testQrCodeTokenizeExistingMpansHavingNoMpanTag' => [
        'request' => [
            'url'     => '/qr_code/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'qr_string_mpan_tokenization_success_count' => 1,
                'qr_string_mpan_tokenization_failed_count'  => 0,
            ]
        ]
    ],

    'testQrCodeTokenizeExistingMpansHavingOneMpanTag' => [
        'request' => [
            'url'     => '/qr_code/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'qr_string_mpan_tokenization_success_count' => 1,
                'qr_string_mpan_tokenization_failed_count'  => 0,
            ]
        ]
    ],

    'testQrCodeTokenizeExistingMpansCardVaultRequestFails' => [
        'request' => [
            'url'     => '/qr_code/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'qr_string_mpan_tokenization_success_count' => 0,
                'qr_string_mpan_tokenization_failed_count'  => 1,
            ]
        ]
    ],

];
