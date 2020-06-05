<?php

use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testUpdateFieldsAction' => [
        'request'  => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id"               => "abcdefghijk",
                    'batch_action'                => 'update_entity',
                    'entity'                      => 'merchant_detail',
                    'id'                          => '10000000000000',
                    'business_name'               => 'suresh',
                    'business_registered_address' => 'local suresh address'
                ],
                [
                    "idempotent_id"               => "abcdefghijk",
                    'batch_action'                => 'update_entity',
                    'entity'                      => 'merchant_detail',
                    'id'                          => '100000Razorpay',
                    'business_name'               => 'mukesh',
                    'business_registered_address' => 'local mukesh address'
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        "idempotent_id"               => "abcdefghijk",
                        'batch_action'                => 'update_entity',
                        'entity'                      => 'merchant_detail',
                        'id'                          => '10000000000000',
                        'business_name'               => 'suresh',
                        'business_registered_address' => 'local suresh address'
                    ],
                    [
                        "idempotent_id"               => "abcdefghijk",
                        'batch_action'                => 'update_entity',
                        'entity'                      => 'merchant_detail',
                        'id'                          => '100000Razorpay',
                        'business_name'               => 'mukesh',
                        'business_registered_address' => 'local mukesh address'
                    ],
                ],
            ],
        ],
    ],

    'testUpdateFieldsInvalidAction' => [
        'request' => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id"               => "abcdefghijk",
                    'batch_action'                => 'update_invalid_action',
                    'entity'                      => 'merchant_detail',
                    'id'                          => '10000000000000',
                    'business_name'               => 'suresh',
                    'business_registered_address' => 'local suresh address'
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'error'            => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_BATCH_ACTION_NOT_SUPPORTED,
                        ],
                        'http_status_code' => 400,
                    ],
                ],
            ],
        ],
    ],

    'testUpdateFieldsInvalidEntity' => [
        'request' => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id" => "abcdefghijk",
                    'batch_action'  => 'update_entity',
                    'entity'        => 'merchant_detl',
                    'id'            => '10000000000000',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'error'            => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_BATCH_ACTION_ENTITY_NOT_SUPPORTED,
                        ],
                        'http_status_code' => 400,
                    ],
                ],
            ],
        ],
    ],

    'testGetBatchActions' => [
        'request'  => [
            'url'    => '/batch_actions',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'update_entity',
                'merchant_action',
            ]
        ],
    ],

    'testMerchantSuspendBatch' => [
        'request' => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id" => "abcdefghijk",
                    'batch_action'  => 'merchant_action',
                    'entity'        => 'merchant',
                    'id'            => '10000000000000',
                    'action'        => "suspend",
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        "idempotent_id" => "abcdefghijk",
                        'batch_action'  => 'merchant_action',
                        'entity'        => 'merchant',
                        'id'            => '10000000000000',
                        'action'        => "suspend",
                    ],
                ],
            ],
        ],
    ],

    'testMerchantAlreadySuspendMerchantBatch' => [
        'request'  => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id" => "abcdefghijk",
                    'batch_action'  => 'merchant_action',
                    'entity'        => 'merchant',
                    'id'            => '10000000000000',
                    'action'        => "suspend",
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        "idempotent_id"    => "abcdefghijk",
                        'batch_action'     => 'merchant_action',
                        'entity'           => 'merchant',
                        'id'               => '10000000000000',
                        'action'           => "suspend",
                        'error'            => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED
                        ],
                        'http_status_code' => 400,
                    ],
                ],
            ],
        ],
    ],

    'testMerchantUnsuspendBatch' => [
        'request' => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id" => "abcdefghijk",
                    'batch_action'  => 'merchant_action',
                    'entity'        => 'merchant',
                    'id'            => '10000000000000',
                    'action'        => "unsuspend",
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        "idempotent_id" => "abcdefghijk",
                        'batch_action'  => 'merchant_action',
                        'entity'        => 'merchant',
                        'id'            => '10000000000000',
                        'action'        => "unsuspend",
                    ],
                ],
            ],
        ],
    ],

    'testMerchantAlreadyUnuspendMerchantBatch' => [
        'request'  => [
            'url'     => '/entities/bulk-update',
            'method'  => 'POST',
            'content' => [
                [
                    "idempotent_id" => "abcdefghijk",
                    'batch_action'  => 'merchant_action',
                    'entity'        => 'merchant',
                    'id'            => '10000000000000',
                    'action'        => "unsuspend",
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items'  => [
                    [
                        "idempotent_id"    => "abcdefghijk",
                        'batch_action'     => 'merchant_action',
                        'entity'           => 'merchant',
                        'id'               => '10000000000000',
                        'action'           => "unsuspend",
                        'error'            => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_SUSPENDED
                        ],
                        'http_status_code' => 400,
                    ],
                ],
            ],
        ],
    ],


    'testGetBatchActionEntities' => [
        'request'  => [
            'url'    => '/batch_action_entities',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'merchant_detail',
                'merchant',
            ]
        ],
    ],
];
