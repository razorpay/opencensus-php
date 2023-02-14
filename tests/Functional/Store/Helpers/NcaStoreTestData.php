<?php

return [
    'testRequestPassingFromApitoNca' => [
        'request'  => [
            'url'     => '/nca/store',
            'method'  => 'post',
            'content' => [
                [
                    "title" => "Cherry",
                    "description" => "Description of the Chocolate",
                    "currency" => "INR",
                    "expire_by" => 1671779592,
                    "type" => "store",
                    "meta_data"=> null,
                    "support_email"=> "test@gmail.com",
                    "support_contact"=> "12345565",
                    "mode"=> "test",
                    "notes"=> [
                        "key1"=> "Select your favourite Chocolate"
                    ],
                    "line_items" =>[
                        [
                            "description"=> "line item number 1",
                            "status"=> "active",
                            "catalog_id"=> "ERDRR5qKOelMAo",
                            "entity_type"=> "price",
                            "position"=> 1,
                            "mandatory"=> true,
                            "entity_details"=> [
                                "amount"=> 100,
                                "currency"=> "INR",
                                "min_units"=> 10,
                                "max_units"=> 100,
                                "min_amount"=> 10,
                                "max_amount"=> 1000,
                                "discount"=> 5
                            ]
                        ],
                    ],
                ]
            ],
        ],
        'response' => [
            'status'  => 201,
            'content' => [
                'title'         => 'test store',
                'description'   => 'test store description',
                'slug'          => 'test-store',
                'status'        => 'active',
                'store_url'     => 'https://stores.razorpay.com/test-store'

            ],
        ],
    ],
];
