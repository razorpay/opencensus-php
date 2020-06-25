<?php


return [
    'testCompositeExpands' => [
        'request' => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url' => '/vendor-payments/composite-expands',
            'content' => [
                'user_ids' => ['10000000000000'],
                'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                'contact_id' => 'cont_Dsp92d4N1Mmm6Q',
                'payout_ids' => ['pout_DuuYxmO7Yegu3x']
            ],
        ],
        'response' => [
            'content' => [
                'merchant' => [],
                'users' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id' => '10000000000000',
                            'name' => 'test-me'
                        ]
                    ]
                ],
                'fund_accounts' => [
                    'fa_D6Z9Jfir2egAUT' => [
                        'id'           => 'fa_D6Z9Jfir2egAUT',
                        'account_type' => 'bank_account'
                    ]
                ],
                'contacts' => [
                    'cont_Dsp92d4N1Mmm6Q' => [
                        'id'   => 'cont_Dsp92d4N1Mmm6Q',
                        'name' => 'test_contact'
                    ]
                ],
                'payouts' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id' => 'pout_DuuYxmO7Yegu3x',
                            'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                            'fund_account' => [
                                'id' => 'fa_D6Z9Jfir2egAUT',
                                'contact' => [
                                    'id' => 'cont_Dsp92d4N1Mmm6Q',
                                    'name' => 'test_contact'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]

    ],

    'testCompositeExpandsWhenOnlyPayoutIsPassed' => [
        'request' => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url' => '/vendor-payments/composite-expands',
            'content' => [
                'payout_ids' => ['pout_DuuYxmO7Yegu3x']
            ],
        ],
        'response' => [
            'content' => [
                'merchant' => [],
                'fund_accounts' => [
                    'fa_D6Z9Jfir2egAUT' => [
                        'id'           => 'fa_D6Z9Jfir2egAUT',
                        'account_type' => 'bank_account'
                    ]
                ],
                'contacts' => [
                    'cont_Dsp92d4N1Mmm6Q' => [
                        'id'   => 'cont_Dsp92d4N1Mmm6Q',
                        'name' => 'test_contact'
                    ]
                ],
                'payouts' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'id' => 'pout_DuuYxmO7Yegu3x',
                            'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                            'fund_account' => [
                                'id' => 'fa_D6Z9Jfir2egAUT',
                                'contact' => [
                                    'id' => 'cont_Dsp92d4N1Mmm6Q',
                                    'name' => 'test_contact'
                                ]
                            ]
                        ]
                    ]
                ]


            ]
        ]

    ],

    'testVendorPaymentBulkCancel' => [
        'request'  => [
            'method' => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'    => '/vendor-payments/bulk-cancel',
            'content' => [
                    'vendor_payments_ids' => ['vdpm_F2qwMZe97QTGG1'],
                    'cancellation_reason' => 'some reson'
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorPaymentGetOcrData' => [
        'request'  => [
            'method' => 'GET',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'    => '/vendor-payments/get-ocr-data/ocr_1234556',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],
];
