<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

use RZP\Tests\Functional\Fixtures\Entity\Workflow;

return [
    'testCreateWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000000,
                        'max_amount'	=>	null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'     => [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  0,
                        'max_amount'    =>  100000
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  100000,
                        'max_amount'    =>  1000000
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'min_amount'    => 1000000,
                        'max_amount'    => null
                    ]
                ]
            ]
        ],
    ],

    'testCreateRulesWithOverlappingRanges' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100001
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000000,
                        'max_amount'	=>  null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ranges provided are not continuous and complete',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRulesWithRangesLeavingGaps' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100002,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000001,
                        'max_amount'	=>  null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ranges provided are not continuous and complete',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRulesWithExtraRanges' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>	null
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>  200000
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ranges provided are not continuous and complete',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRulesWithWrongWorkflowId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'workflow_id'   =>  'workflow_1000000wrongId',
                        'min_amount'   =>      0,
                        'max_amount'   =>      null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                        'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                        'description'=> 'Workflow does not have create_payout permission'
                    ]
                ],
            'status_code' => 400,
            ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT,
        ],
    ],

    'testCreateRulesWithDuplicateWorkflowIds' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000000,
                        'max_amount'	=>	null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Each workflow can have only one amount range',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [

                'error' => [
                    'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'=> 'Workflow payout amount rules have already been created'
                ]

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_RULES_UPDATE_OR_DELETE_NOT_ALLOWED,
        ],
    ],

    'testGetMerchantIdsForCreatePayoutWorkflowPermission' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/merchants/workflows/permissions/create_payout',
            'content' => [],
        ],
        'response' => [
            'content' => [

                "entity"    =>  "collection",
                'count'     =>  2,
                'items'     =>  [

                ]

            ],
        ]
    ],

    'testGetMerchantWorkflowPayoutAmountRules' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/admin-workflows/rules/payout_amount?expand[]=steps&expand[]=steps.role',
            'content' => [],
        ],
        'response' => [
            'content' => [

                "entity"    =>  "collection",
                'count'     =>  3,
                'items'     =>  [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
                        'steps'         =>  [
                            "entity"    =>  "collection",
                            'count'     =>  0,
                            'items'     =>  []
                        ]
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  100,
                        'max_amount'    =>  1000,
                        'steps'         =>  [
                            "entity"    =>  "collection",
                            'count'     =>  0,
                            'items'     =>  []
                        ]
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  1000,
                        'max_amount'    =>  null,
                        'steps'         =>  [
                            "entity"    =>  "collection",
                            'count'     =>  3,
                            'items'     =>  [
                                [
                                    'role_id'           =>  'role_RzpAdminRoleId',
                                    'workflow_id'       =>  'workflow_workflowId1000',
                                    'reviewer_count'    =>  1,
                                    'op_type'           =>  'or',
                                    'level'             =>  1,
                                    'role'              =>  [
                                        'id'                => 'role_RzpAdminRoleId',
                                        'name'              => 'SuperAdmin',
                                        'description'       => 'Manager of roles',
                                        'org_id'            => 'org_100000razorpay',
                                    ]
                                ],
                                [
                                    'role_id'           => 'role_RzpChekrRoleId',
                                    'workflow_id'       => 'workflow_workflowId1000',
                                    'reviewer_count'    =>  1,
                                    'op_type'           => 'or',
                                    'level'             =>  1,
                                    'role'              => [
                                        'id'                => 'role_RzpChekrRoleId',
                                        'name'              => 'Checker',
                                        'description'       => 'Manager of roles',
                                        'org_id'            => 'org_100000razorpay',
                                    ]
                                ],
                                [
                                    'role_id'           => 'role_RzpMakerRoleId',
                                    'workflow_id'       => 'workflow_workflowId1000',
                                    'reviewer_count'    =>  1,
                                    'op_type'           => 'and',
                                    'level'             =>  2,
                                    'role'              => [
                                        'id'                => 'role_RzpMakerRoleId',
                                        'name'              => 'Maker',
                                        'description'       => 'Manager of roles',
                                        'org_id'            => 'org_100000razorpay'
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ],
        ]
    ],

    'testGetMerchantWorkflowPayoutAmountRulesProxyAuth' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [],
        ],
        'response' => [
            'content' => [

                "entity"    =>  "collection",
                'count'     =>  3,
                'items'     =>  [
                    [
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
                    ],
                    [
                        'min_amount'    =>  100,
                        'max_amount'    =>  1000,
                    ],
                    [
                        'min_amount'    =>  1000,
                        'max_amount'    =>  null,
                    ]
                ]
            ],
        ]
    ],

    'testCreateWorkflowRulesWithWrongPermission' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'=> 'Workflow does not have create_payout permission'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT,
        ],
    ],

    'testCreateWorkflowPayoutAmountRulesWithNoWorkflowId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows/rules/payout_amount',
            'content' => [
                'rules' => [
                    [
                        'min_amount'	=>	0,
                        'max_amount'	=>	100000
                    ],
                    [
                        'min_amount'	=>	100000,
                        'max_amount'	=>	1000000
                    ],
                    [
                        'min_amount'	=>	1000000,
                        'max_amount'	=>	null
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'     => [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  0,
                        'max_amount'    =>  100000
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  100000,
                        'max_amount'    =>  1000000
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'min_amount'    => 1000000,
                        'max_amount'    => null
                    ]
                ]
            ]
        ],
    ],

    'testEditPayoutWorkflow' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/admin-workflows/rules/payout_amount',
            'content' => [
                "workflows" => [
                    [
                        "name" => "Payout-Workflow-EDnqetiSK0XR7R-3",
                        "permissions" => [],
                        "levels" => [
                            [
                                "op_type"   => "and",
                                "steps"     => [
                                    [
                                        "role_id"        => null,
                                        "reviewer_count" => 1,
                                    ],
                                ],
                                "level" => 1,
                            ],
                        ],
                        "payout_amount_rules" => [
                            [
                                "min_amount" => 0,
                                "max_amount" => 10,
                            ]
                        ],
                        "org_id" => "org_100000razorpay",
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        "min_amount" => 0,
                        "max_amount" => 10,
                        "workflow" => [
                            "name" => "Payout-Workflow-EDnqetiSK0XR7R-3",
                            "steps" => [
                                [
                                    "level" => 1,
                                    "op_type" => "and",
                                    "reviewer_count" => 1,
                                ]
                            ],
                            "permissions" => [
                                [
                                    "name" => "create_payout",
                                    "description" => "Merchant can create a new payout",
                                    "category" => "payouts",
                                    "assignable" => false,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testEditPayoutWorkflowForCAC' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/admin-workflows/rules/payout_amount',
            'content' => [
                "workflows" => [
                    [
                        "name" => "Payout-Workflow-EDnqetiSK0XR7R-3",
                        "permissions" => [],
                        "levels" => [
                            [
                                "op_type"   => "and",
                                "steps"     => [
                                    [
                                        "role_id"        => null,
                                        "reviewer_count" => 1,
                                    ],
                                ],
                                "level" => 1,
                            ],
                        ],
                        "payout_amount_rules" => [
                            [
                                "min_amount" => 0,
                                "max_amount" => 10,
                            ]
                        ],
                        "org_id" => "org_100000razorpay",
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => array (
                'entity' => 'collection',
                'count' => 1,
                'items' =>
                    array (
                        0 =>
                            array (
                                'min_amount' => 0,
                                'max_amount' => 10,
                                'merchant_id' => '10000000000000',
                                'workflow' =>
                                    array (
                                        'merchant_id' => '10000000000000',
                                        'steps' =>
                                            array (
                                                0 =>
                                                    array (
                                                        'level' => 1,
                                                        'role_id' => 'role_finance_l1',
                                                        'op_type' => 'and',
                                                        'reviewer_count' => 1,
                                                    ),
                                            ),
                                        'permissions' =>
                                            array (
                                                0 =>
                                                    array (
                                                        'name' => 'create_payout',
                                                        'description' => 'Merchant can create a new payout',
                                                        'category' => 'payouts',
                                                        'assignable' => false,
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            )
        ],
    ],

    'testEditActivePayoutWorkflow' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/admin-workflows/rules/payout_amount',
            'content' => [
                "workflows" => [
                    [
                        "name" => "Payout-Workflow-EDnqetiSK0XR7R-3",
                        "permissions" => [],
                        "levels" => [
                            [
                                "op_type"   => "and",
                                "steps"     => [
                                    [
                                        "role_id"        => null,
                                        "reviewer_count" => 1,
                                    ],
                                ],
                                "level" => 1,
                            ],
                        ],
                        "payout_amount_rules" => [
                            [
                                "min_amount" => 0,
                                "max_amount" => 10,
                            ]
                        ],
                        "org_id" => "org_100000razorpay",
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'=> PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'=> 'Updating or Deleting a workflow is not allowed when there are open actions'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED,
        ],
    ],
];
