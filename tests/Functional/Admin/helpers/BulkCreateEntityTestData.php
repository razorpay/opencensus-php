<?php

return [
    'testCreateMerchantEmailsBulk' => [
        'request'  => [
            'content' => [
                'type' => 'merchant_email',
                'data' => [
                    [
                        'merchant_id' => '10000000000000',
                        'email'       => 'omprakash.ahrodia@spicejet.com,fraudcontrol@spicejet.com,nimisha.sharma@spicejet.com,deepak.kumar@razorpay.com',
                        'type'        => 'chargeback'
                    ],
                    [
                        'merchant_id' => '10000000000000',
                        'email'       => 'omprakash.ahrodia@spicejet.com,fraudcontrol@spicejet.com,nimisha.sharma@spicejet.com,deepak.kumar@razorpay.com',
                        'type'        => 'dispute'
                    ],
                    [
                        'merchant_id' => '100000Razorpay',
                        'email'       => 'abhishek@dailyninja.in,sagar@dailyninja.in,prajval@dailyninja.in,hidayath@dailyninja.in',
                        'type'        => 'chargeback'
                    ],
                    [
                        'merchant_id' => '100000Razorpay',
                        'email'       => 'abhishek@dailyninja.in,sagar@dailyninja.in,prajval@dailyninja.in,hidayath@dailyninja.in',
                        'type'        => 'dispute'
                    ],
                    [
                        'merchant_id' => '100AtomAccount',
                        'email'       => 'orders@tonguestun.in,william.emmanual@tonguestun.com',
                        'type'        => 'chargeback'
                    ],
                    [
                        'merchant_id' => '100AtomAccount',
                        'email'       => 'orders@tonguestun.in,william.emmanual@tonguestun.com',
                        'type'        => 'dispute'
                    ]]
            ],
            'url'     => '/admin/bulkcreate',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'success_count'=> 6,
            ],
        ],
    ],

];
