<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePlanWithItemId' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'period'    => 'monthly',
                'interval'  => 2,
                'item_id'   => 'item_1000000000item',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'plan',
                'interval' => 2,
                'period'   => 'monthly',
                'notes'    => [],
                'item'     => [
                    'active'      => true,
                    'name'        => 'Some item name',
                    'description' => 'Some item description',
                    'amount'      => 100000,
                    'currency'    => 'INR',
                    'type'        => 'plan',
                ]
            ],
        ],
    ],

    'testCreatePlanWithItemIdButWrongItemType' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'period'            => 'monthly',
                'interval'          => 2,
                'item_id'           => 'item_1000000000item',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Can only reuse an item of the same item type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE,
        ],
    ],

    'testCreatePlanWithoutItemId' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'post',
            'content' => [
                'period'    => 'monthly',
                'interval'  => 2,
                'item'      => [
                    'name'     => 'test plan',
                    'amount'   => 20000,
                    'currency' => 'INR',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'plan',
                'interval' => 2,
                'period'   => 'monthly',
                'notes'    => [],
                'item'     => [
                    'active'   => true,
                    'name'     => 'test plan',
                    'amount'   => 20000,
                    'currency' => 'INR',
                    'type'     => 'plan',
                ]
            ],
        ],
    ],

    'testCreatePlanWithoutAnyItem' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'period'    => 'monthly',
                'interval'  => 2,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The item id field is required when item is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePlanWithBadMonthlyIntervalPeriod' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'post',
            'content' => [
                'period'    => 'monthly',
                'interval'  => 140,
                'item_id'   => 'item_1000000000item',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Interval provided exceed the maximum interval (120) allowed for the given period (monthly)',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePlanWithBadYearlyIntervalPeriod' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'post',
            'content' => [
                'period'    => 'yearly',
                'interval'  => 12,
                'item_id'   => 'item_1000000000item',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Interval provided exceed the maximum interval (10) allowed for the given period (yearly)',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchPlan' => [
        'request' => [
            'url'     => '/plans/plan_1000000000plan',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'       => 'plan_1000000000plan',
                'entity'   => 'plan',
                'interval' => 2,
                'period'   => 'monthly',
                'notes'    => [],
                'item'     => [
                    'active'   => true,
                    'name'     => 'test plan',
                    'amount'   => 2000,
                    'currency' => 'INR',
                    'type'     => 'plan',
                ],
            ],
        ],
    ],

    'testFetchMultiplePlan' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'       => 'plan_1000000001plan',
                        'entity'   => 'plan',
                        'interval' => 2,
                        'period'   => 'monthly',
                        'notes'    => [],
                        'item'     => [
                            'active'   => true,
                            'name'     => 'Plan #2',
                            'amount'   => 2000,
                            'currency' => 'INR',
                            'type'     => 'plan',
                        ],
                    ],
                    [
                        'id'       => 'plan_1000000000plan',
                        'entity'   => 'plan',
                        'interval' => 2,
                        'period'   => 'monthly',
                        'notes'    => [],
                        'item'     => [
                            'active'   => true,
                            'name'     => 'test plan',
                            'amount'   => 2000,
                            'currency' => 'INR',
                            'type'     => 'plan',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateAddon' => [
        'request' => [
            'url'       => '/subscriptions/{subscriptionId}/addons',
            'method'    => 'post',
            'content'   => [
                'quantity'  => 2,
                'item'      => [
                    'name'          => 'test addon',
                    'amount'        => 1000,
                    'currency'      => 'INR',
                    'description'   => 'test addon desc'
                ]
            ]
        ],
        'response' => [
            'content'   => [
                'item'  => [
                    'name'  => 'test addon',
                    'type'  => 'addon',
                ],
                'invoice_id'    => null,
            ]
        ]
    ],

    'testFetchAddon' => [
        'request' => [
            'url'       => '/addons/{addonId}',
            'method'    => 'get',
            'content'   => [
            ]
        ],
        'response'  => [
            'content'   => [
                'entity'        => 'addon',
                'item'          => [
                    'name'  => 'Some item name',
                    'type'  => 'addon',
                ],
                'invoice_id'    => null,
            ]
        ]
    ],

    'testFetchMultipleAddons' => [
        'request' => [
            'url'       => '/addons/',
            'method'    => 'get',
            'content'   => [
                'subscription_id' => '{subscriptionId}',
            ]
        ],
        'response'  => [
            'content'   => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'addon',
                        'invoice_id' => null,
                    ]
                ]
            ]
        ]
    ],

    'testDeleteAddon' => [
        'request'   => [
            'url'       => '/addons/{addonId}',
            'method'    => 'delete',
        ],
        'response'  => [
            'content' => []
        ]
    ],

    'testDeleteAddonAssociatedWithInvoice' => [
        'request'   => [
            'url'       => '/addons/{addonId}',
            'method'    => 'delete'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Delete operation cannot be performed on the addon',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ADDON_DELETE_NOT_ALLOWED,
        ],
    ],

    'testFetchDueAddons' => [
        'request' => [
            'url'       => '/subscriptions/{subscriptionId}/addons/due',
            'method'    => 'get',
        ],
        'response'  => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'entity'        => 'addon',
                        'invoice_id'    => null,
                        'item'          => [
                            'id'    => 'item_3000000000item',
                            'type'  => 'addon',
                        ]
                    ]
                ]
            ]
        ]
    ],

    'createSubscriptionForAuthTxn' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'plan_id' => 'plan_1000000000plan',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                'total_count' => 6,
                'paid_count' => 0,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'createSubscriptionForAuthTxnWithStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'plan_id'         => 'plan_1000000000plan',
                'status'          => 'created',
                'current_start'   => null,
                'current_end'     => null,
                'ended_at'        => null,
                'quantity'        => 1,
                // 'token_id'        => null,
                'notes'           => [],
                'charge_at'       => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at'        => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'          => 1542652200, // 12-20-2018, 12:00:00 AM
                'total_count'     => 6,
                'paid_count'      => 0,
                'auth_attempts'   => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'testCreateSubscriptionWithoutCustomerId' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testCreateSubscriptionWithoutPlanId' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'plan_id should be sent in the request to create a subscription.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithNoStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'plan_id' => 'plan_1000000000plan',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                'total_count' => 6,
                'paid_count' => 0,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'testCreateSubscriptionWithStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'plan_id' => 'plan_1000000000plan',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1542652200, // 12-20-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'testCreateSubscriptionWeeklyIntervalWithStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'plan_id' => 'plan_1000000000plan',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1522434600, // 3-31-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'testCreateSubscriptionWithNoStartAtAndWithAddon' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'plan_id'         => 'plan_1000000000plan',
                'quantity'        => 1,
                'total_count'     => 6, // Every two months
                'customer_notify' => 0,
                'addons'        => [
                    [
                        'item' => [
                            'amount' => 300,
                            'currency' => 'INR',
                            'name' => 'Sample Upfront Amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                // 'upfront_amount' => 300,
                'total_count' => 6,
                'paid_count' => 0,
            ],
        ],
    ],

    'testCreateSubscriptionWithMultipleQuantityAddon' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'plan_id'         => 'plan_1000000000plan',
                'quantity'        => 1,
                'total_count'     => 6, // Every two months
                'customer_notify' => 0,
                'addons'        => [
                    [
                        'quantity' => 4,
                        'item' => [
                            'amount' => 300,
                            'currency' => 'INR',
                            'name' => 'Sample Upfront Amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                // 'upfront_amount' => 300,
                'total_count' => 6,
                'paid_count' => 0,
            ],
        ],
    ],

    'testCreateSubscriptionWithStartAtAndAddon' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'plan_id'         => 'plan_1000000000plan',
                'quantity'        => 1,
                'total_count'     => 6, // Every two months
                'start_at'        => 1516386600,
                'customer_notify' => 0,
                'addons'        => [
                    [
                        'item' => [
                            'amount' => 300,
                            'currency' => 'INR',
                            'name' => 'Sample Upfront Amount'
                        ]
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'plan_id' => 'plan_1000000000plan',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                // 'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1542652200, // 12-20-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ],
        ],
    ],

    'testCreateSubscriptionWithOneYearLateStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 2116002600, // 1-20-2037, 12:00:00 AM
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'start_at must be less than 10 year/s from now.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithPastTime' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 1484850600, // 1-20-2017, 12:00:00 AM
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'start_at cannot be lesser than the current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithTotalCount' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'total_count'   => 6, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'status'        => 'created',
                'current_start' => null,
                'current_end'   => null,
                'ended_at'      => null,
                'quantity'      => 1,
                // 'token_id'      => null,
                'notes'         => [],
                'charge_at'     => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1542652200, // 11-20-2018, 12:00:00 AM
                'total_count'   => 6,
                'paid_count'    => 0
            ],
        ],
    ],

    'testCreateSubscriptionWithEndAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1542652200, // Every two months
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'status'        => 'created',
                'current_start' => null,
                'current_end'   => null,
                'ended_at'      => null,
                'quantity'      => 1,
                // 'token_id'      => null,
                'notes'         => [],
                'charge_at'     => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1542652200, // 12-20-2018, 12:00:00 AM
                'total_count'   => 6,
                'paid_count'    => 0
            ],
        ],
    ],

    'testCreateSubscriptionWithBothTotalCountAndEndAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1545244200, // Every two months
                'total_count'   => 6,
                'customer_notify' => 0,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either end_at or total_count should be sent and not both.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
        ],
    ],

    'testCreateSubscriptionWithEndAtLesserThanStartAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'plan_id'       => 'plan_1000000000plan',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1505244200, // Every two months
                'customer_notify' => 0,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'end_at cannot be lesser than start_at.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithVeryFarEndAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'plan_id'       => 'plan_1000000000plan',
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 2116002600, // 1-20-2020, 12:00:00 AM
                'customer_notify' => 0,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'end_at should be within 10 year/s of start_at.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithoutTotalCountAndEndAt' => [
        'request' => [
            'url' => '/subscriptions',
            'method' => 'post',
            'content' => [
                'plan_id'       => 'plan_1000000000plan',
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'customer_notify' => 0,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The total count field is required when end at is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetInvoicesForSubscription' => [
        'request' => [
            'url'       => '/invoices',
            'method'    => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'entity'        => 'invoice',
                        'customer_id'   => 'cust_100000customer',
                        'payment_id'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchSubscription' => [
        'request' => [
            // 'url'     => '/subscriptions/{id}',
            'method'  => 'get',
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'plan_id'         => 'plan_1000000000plan',
                'quantity'        => 1,
                'total_count'     => 6,
                'customer_notify' => 0,
            ],
        ],
        'response' => [
            'content' => [
                // 'id'               => 'sub_7oKmlxFlg8HlDN',
                'plan_id'          => 'plan_1000000000plan',
                'customer_id'      => 'cust_100000customer',
                // 'token_id'         => null,
                'status'           => 'created',
                'quantity'         => 1,
                'total_count'      => 6,
                'paid_count'       => 0,
                'auth_attempts'    => 0,
                'customer_notify'  => false,
                'notes'            => [],
                'current_start'    => null,
                'current_end'      => null,
                'start_at'         => null,
                'end_at'           => null,
                'charge_at'        => null,
                'ended_at'         => null,
            ],
        ],
    ],

    'testFetchMultipleSubscription' => [
        'request' => [
            'url'     => '/subscriptions',
            'method' => 'get',
            'content' => [
                'plan_id' => 'plan_1000000000plan'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'        => 'subscription',
                        'plan_id'       => 'plan_1000000000plan',
                        'customer_id'   => 'cust_100000customer',
                        'status'        => 'created'
                    ]
                ]
            ],
        ],
    ],

    'testFetchMultipleSubscriptionWithEmailFilter' => [
        'request' => [
            'url'     => '/subscriptions',
            'method' => 'get',
            'content' => [
                'plan_id' => 'plan_1000000000plan',
                'customer_email' => 'test@razorpay.com',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'        => 'subscription',
                        'plan_id'       => 'plan_1000000000plan',
                        'customer_id'   => 'cust_100000customer',
                        'status'        => 'created'
                    ]
                ]
            ],
        ],
    ],

    'testFetchMultipleSubscriptionWithEmailFilterNegative' => [
        'request' => [
            'url'     => '/subscriptions',
            'method' => 'get',
            'content' => [
                'plan_id' => 'plan_1000000000plan',
                'customer_email' => 'test1@razorpay.com',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => []
            ]
        ],
    ],

    'testSubscriptionCharge' => [
        'request' => [
            'url' => '/subscriptions/charge',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'subscriptionWebhookData' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'contains' => [
                'subscription',
            ],
            'payload'  => [
                'subscription' => [
                ],
            ],
        ],
    ],

    'makePreferencesCall' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testSubscriptionCancel' => [
        'request' => [
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'subscription',
                'plan_id' => 'plan_1000000000plan',
                'customer_id' => 'cust_100000customer',
                'status' => 'cancelled',
                'total_count' => 6,
                'paid_count' => 1,
            ]
        ],
    ],

    'testSubscriptionCancelFuture' => [
        'request' => [
            'method'    => 'post',
            'content'   => [
                'cancel_at_cycle_end'   => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity'                => 'subscription',
                'plan_id'               => 'plan_1000000000plan',
                'customer_id'           => 'cust_100000customer',
                'status'                => 'active',
                'total_count'           => 6,
                'paid_count'            => 1,
                'ended_at'              => null,
            ]
        ]
    ],

    'testSubscriptionCancelBasic' => [
        'request' => [
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'subscription',
                'plan_id' => 'plan_1000000000plan',
                'customer_id' => 'cust_100000customer',
                'status' => 'cancelled',
                'total_count' => 6,
                'paid_count' => 0,
                'current_start' => null,
                'current_end' => null,
                'quantity' => 1,
                'notes' => [],
                'charge_at' => null,
                'start_at' => 1516386600,
                'end_at' => 1542652200,
                'auth_attempts' => 0,
                'customer_notify' => false,
            ]
        ],
    ],

    'subscriptionWebhookDataForAuthFailurePending' => [
        'mode'  => 'test',
        'event' => [
            'entity'    => 'event',
            'event'     => 'subscription.pending',
            'contains' => [
                'subscription',
            ],
            'payload'  => [
                'subscription' => [
                    'entity' => [
                        'entity'            => 'subscription',
                        'plan_id'           => 'plan_1000000000plan',
                        'customer_id'       => 'cust_100000customer',
                        'status'            => 'pending',
                        'current_start'     => 1521484200,
                        'current_end'       => 1526754600,
                        'ended_at'          => null,
                        'quantity'          => 1,
                        'notes'             => [],
                        'charge_at'         => 1521570600,
                        'start_at'          => 1516386600,
                        'end_at'            => 1542652200,
                        'auth_attempts'     => 1,
                        'total_count'       => 6,
                        'paid_count'        => 1,
                        'customer_notify'   => false,
                    ]
                ],
            ],
        ],
    ],

    'subscriptionWebhookDataForCaptureFailurePending' => [
        'mode'  => 'test',
        'event' => [
            'entity'    => 'event',
            'event'     => 'subscription.pending',
            'contains' => [
                // 'subscription', 'payment'
                'subscription'
            ],
            'payload'  => [
                'subscription' => [
                    'entity' => [
                        'entity'            => 'subscription',
                        'plan_id'           => 'plan_1000000000plan',
                        'customer_id'       => 'cust_100000customer',
                        'status'            => 'pending',
                        'current_start'     => 1521484200,
                        'current_end'       => 1526754600,
                        'ended_at'          => null,
                        'quantity'          => 1,
                        'notes'             => [],
                        'charge_at'         => 1521570600,
                        'start_at'          => 1516386600,
                        'end_at'            => 1542652200,
                        'auth_attempts'     => 1,
                        'total_count'       => 6,
                        'paid_count'        => 1,
                        'customer_notify'   => false,
                    ]
                ],
                // 'payment' => [
                //     'entity' => [
                //         'entity'            => 'payment',
                //         'amount'            => 2000,
                //         'currency'          => 'INR',
                //         'status'            => 'authorized',
                //         'international'     => false,
                //         'method'            => 'card',
                //         'amount_refunded'   => 0,
                //         'refund_status'     => null,
                //         'captured'          => false,
                //         'description'       => 'Recurring Payment via Subscription',
                //         'bank'              => null,
                //         'wallet'            => null,
                //         'vpa'               => null,
                //         'email'             => 'test@razorpay.com',
                //         'contact'           => '+911234567890',
                //         'customer_id'       => 'cust_100000customer',
                //         'notes'             => [],
                //         'fee'               => null,
                //         'service_tax'       => null,
                //         'error_code'        => null,
                //         'error_description' => null,
                //         'acquirer_data'     => [],
                //     ]
                // ],
            ],
        ],
    ],

    'subscriptionWebhookDataForSuccessAfterPending' => [
        'mode'  => 'test',
        'event' => [
            'entity'    => 'event',
            'event'     => 'subscription.activated',
            'contains' => [
                // 'subscription', 'payment'
                'subscription'
            ],
            'payload'  => [
                'subscription' => [
                    'entity' => [
                        'entity'            => 'subscription',
                        'plan_id'           => 'plan_1000000000plan',
                        'customer_id'       => 'cust_100000customer',
                        'status'            => 'active',
                        'ended_at'          => null,
                        'quantity'          => 1,
                        'notes'             => [],
                        'auth_attempts'     => 0,
                        'total_count'       => 6,
                        'paid_count'        => 2,
                        'customer_notify'   => false,
                    ]
                ],
                // 'payment' => [
                //     'entity' => [
                //         'entity'            => 'payment',
                //         'amount'            => 2000,
                //         'currency'          => 'INR',
                //         'status'            => 'captured',
                //         'international'     => false,
                //         'method'            => 'card',
                //         'amount_refunded'   => 0,
                //         'refund_status'     => null,
                //         'captured'          => true,
                //         'description'       => 'Recurring Payment via Subscription',
                //         'bank'              => null,
                //         'wallet'            => null,
                //         'vpa'               => null,
                //         'email'             => 'test@razorpay.com',
                //         'contact'           => '+911234567890',
                //         'customer_id'       => 'cust_100000customer',
                //         'notes'             => [],
                //         'fee'               => 40,
                //         'service_tax'       => 0,
                //         'error_code'        => null,
                //         'error_description' => null,
                //         'acquirer_data'     => [],
                //     ]
                // ],
            ],
        ],
    ],

    'subscriptionWebhookDataForCharge' => [
        'mode'  => 'test',
        'event' => [
            'entity'    => 'event',
            'event'     => 'subscription.charged',
            'contains' => [
                // 'subscription', 'payment'
                'subscription'
            ],
            'payload'  => [
                'subscription' => [
                    'entity' => [
                        'entity'            => 'subscription',
                        'plan_id'           => 'plan_1000000000plan',
                        'customer_id'       => 'cust_100000customer',
                        'status'            => 'active',
                        'current_start'     => 1516386600,
                        'current_end'       => 1521484200,
                        'ended_at'          => null,
                        'quantity'          => 1,
                        'notes'             => [],
                        'charge_at'         => 1521484200,
                        'start_at'          => 1516386600,
                        'end_at'            => 1542652200,
                        'auth_attempts'     => 0,
                        'total_count'       => 6,
                        'paid_count'        => 1,
                        'customer_notify'   => false,
                    ]
                ],
                // 'payment' => [
                //     'entity' => [
                //         'entity'            => 'payment',
                //         'amount'            => 2000,
                //         'currency'          => 'INR',
                //         'status'            => 'captured',
                //         'international'     => false,
                //         'method'            => 'card',
                //         'amount_refunded'   => 0,
                //         'refund_status'     => null,
                //         'captured'          => true,
                //         'description'       => 'Recurring Payment via Subscription',
                //         'bank'              => null,
                //         'wallet'            => null,
                //         'vpa'               => null,
                //         'email'             => 'test@razorpay.com',
                //         'contact'           => '+911234567890',
                //         'customer_id'       => 'cust_100000customer',
                //         'notes'             => [],
                //         'fee'               => 40,
                //         'service_tax'       => 0,
                //         'error_code'        => null,
                //         'error_description' => null,
                //         'acquirer_data'     => [],
                //     ]
                // ],
            ],
        ],
    ],

    'subscriptionWebhookDataForFirstActivated' => [
        'mode'  => 'test',
        'event' => [
            'entity'    => 'event',
            'event'     => 'subscription.activated',
            'contains' => [
                'subscription',
            ],
            'payload'  => [
                'subscription' => [
                    'entity' => [
                        'entity'            => 'subscription',
                        'plan_id'           => 'plan_1000000000plan',
                        'customer_id'       => 'cust_100000customer',
                        'status'            => 'active',
                        'current_start'     => null,
                        'current_end'       => null,
                        'ended_at'          => null,
                        'quantity'          => 1,
                        'notes'             => [],
                        'auth_attempts'     => 0,
                        'total_count'       => 6,
                        'paid_count'        => 0,
                        'customer_notify'   => false,
                        // These fields are like this because this is
                        // first activated. In first activated we fire
                        // webhook first and then make a charge, unlike
                        // other active fires.
                        // 'current_start' => NULL
                        // 'current_end' => NULL
                        // 'paid_count' => integer 0
                    ]
                ],
            ],
        ],
    ],

    'subscriptionWebhookDataForCancel' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event'  => 'subscription.cancelled',
            'contains' => [
                'subscription',
            ],
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'entity'        => 'subscription',
                        'plan_id'       => 'plan_1000000000plan',
                        'customer_id'   => 'cust_100000customer',
                        'status'        => 'cancelled',
                        'current_start' => 1516386600,
                        'current_end'   => 1521484200,
                        'ended_at'      => 1516386601,
                        'quantity'      => 1,
                        'notes'         => [],
                        'charge_at'     => null,
                        'start_at'      => 1516386600,
                        'end_at'        => 1542652200,
                        'auth_attempts' => 0,
                        'total_count'   => 6,
                        'paid_count'    => 1,
                    ]
                ]
            ]
        ]
    ],

    'testSubscriptionCardChangeOnAuthenticated' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Cannot change card for the subscription at this state',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED,
        ],
    ],

    'testSubscriptionHaltedCardChangeFail' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'subscriptionWebhookDataForFutureCancel' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event'  => 'subscription.cancelled',
            'contains' => [
                'subscription',
            ],
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'entity'                => 'subscription',
                        'plan_id'               => 'plan_1000000000plan',
                        'customer_id'           => 'cust_100000customer',
                        'status'                => 'cancelled',
                        'current_start'         => 1516386600,
                        'current_end'           => 1521484200,
                        'ended_at'              => 1516386601,
                        'quantity'              => 1,
                        'notes'                 => [],
                        'charge_at'             => null,
                        'start_at'              => 1516386600,
                        'end_at'                => 1542652200,
                        'auth_attempts'         => 0,
                        'total_count'           => 6,
                        'paid_count'            => 1,
                    ]
                ]
            ]
        ]
    ]
];
