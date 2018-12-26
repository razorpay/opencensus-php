<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'hitachi',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'terminal',
                'status'        => 'created',
                'total_count'   => 3,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkTerminalCreationValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'hitachi',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::HITACHI_RID          => '1111',
                        Header::HITACHI_MERCHANT_ID  => '10NodalAccount',
                        Header::HITACHI_SUB_IDS      => '10000000000000',
                        Header::HITACHI_MID          => '1252836',
                        Header::HITACHI_TID          => '12313',
                        Header::HITACHI_PART_NAME    => 'partname',
                        Header::HITACHI_ME_NAME      => 'mename',
                        Header::HITACHI_ZIPCODE      => 'zipcode',
                        Header::HITACHI_LOCATION     => 'location',
                        Header::HITACHI_CITY         => 'city',
                        Header::HITACHI_STATE        => 'state',
                        Header::HITACHI_COUNTRY      => 'country',
                        Header::HITACHI_MCC          => '3323',
                        Header::HITACHI_TERM_STATUS  => 'termstatus',
                        Header::HITACHI_ME_STATUS    => 'mestatus',
                        Header::HITACHI_ZIPCODE      => 'zipcode',
                        Header::HITACHI_SWIPER_ID    => 'swiperid',
                        Header::HITACHI_SPONSOR_BANK => 'sponsorbank',
                        Header::HITACHI_CURRENCY     => 'INR',
                    ],
                    [
                        Header::HITACHI_RID          => '2222',
                        Header::HITACHI_MERCHANT_ID  => '100000Razorpay',
                        Header::HITACHI_SUB_IDS      => null,
                        Header::HITACHI_MID          => '2222836',
                        Header::HITACHI_TID          => '1233313',
                        Header::HITACHI_PART_NAME    => 'partname',
                        Header::HITACHI_ME_NAME      => 'mename',
                        Header::HITACHI_ZIPCODE      => 'zipcode',
                        Header::HITACHI_LOCATION     => 'location',
                        Header::HITACHI_CITY         => 'city',
                        Header::HITACHI_STATE        => 'state',
                        Header::HITACHI_COUNTRY      => 'country',
                        Header::HITACHI_MCC          => '1323',
                        Header::HITACHI_TERM_STATUS  => 'termstatus',
                        Header::HITACHI_ME_STATUS    => 'mestatus',
                        Header::HITACHI_ZIPCODE      => 'zipcode',
                        Header::HITACHI_SWIPER_ID    => 'swiperid',
                        Header::HITACHI_SPONSOR_BANK => 'sponsorbank',
                        Header::HITACHI_CURRENCY     => 'USD',
                    ]
                ],
            ],
        ],
    ],
];
