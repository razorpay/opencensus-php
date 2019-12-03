<?php
return [
    'testPostRequestForCreatingPayoutLink' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/payout-links',
            'content' => [
                [
                    "amount"      => 1000,
                    "currency"    => "INR",
                    "description" => "This is a test payout",
                    "contact"     => [
                        "contact_id" => null,
                        "name"       => "cskdsds",
                        "email"      => "dsknlds@gmail.com",
                        "contact"    => "12323sddskl"
                    ],
                    "notes"       => ["hi" => "hello"],
                    "receipt"     => "Test Payout Receipt"
                ]
            ]
        ],
        'response' => [
            'content' => [
//                "id"           => "plnk_DnhDjMDHlQEjgM",
//                "contact_id"   => "DnhDjFYg5uLW6R",
                "amount"       => 1000,
//                "merchant_id"  => "D5mLvCOvhuV1hN",
                "user_id"      => null,
                "currency"     => "INR",
                "description"  => "This is a test payout",
                "receipt"      => "Test Payout Receipt",
                "notes"        => [
                    "hi" => "hello"
                ],
                "short_url"    => "http=>//76594130.ngrok.io/i/mGs4ehe",
                "status"       => "issued",
            ]
        ]
    ],
    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
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
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ]
];