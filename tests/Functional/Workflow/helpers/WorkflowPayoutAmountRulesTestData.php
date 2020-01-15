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
            'url'     => '/workflows/rules/payout_amount/admin',
            'content' => [],
        ],
        'response' => [
            'content' => [

                "entity"    =>  "collection",
                'count'     =>  3,
                'items'     =>  [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  1000,
                        'max_amount'    =>  null,
                        'steps'         =>  [

                            [
                                'role_id'           =>  'RzpAdminRoleId',
                                'workflow_id'       =>  'workflowId1000',
                                'reviewer_count'    =>  1,
                                'op_type'           =>  'or',
                                'level'             =>  1,
                                'role'              =>  [
                                    'id'                => 'RzpAdminRoleId',
                                    'name'              => 'SuperAdmin',
                                    'description'       => 'Manager of roles',
                                    'org_id'            => '100000razorpay',
                                ]
                            ],
                            [
                                'role_id'           => 'RzpChekrRoleId',
                                'workflow_id'       => 'workflowId1000',
                                'reviewer_count'    =>  1,
                                'op_type'           => 'or',
                                'level'             =>  1,
                                'role'              => [
                                    'id'                => 'RzpChekrRoleId',
                                    'name'              => 'Checker',
                                    'description'       => 'Manager of roles',
                                    'org_id'            => '100000razorpay',
                                ]
                            ],
                            [
                                'role_id'           => 'RzpMakerRoleId',
                                'workflow_id'       => 'workflowId1000',
                                'reviewer_count'    =>  1,
                                'op_type'           => 'and',
                                'level'             =>  2,
                                'role'              => [
                                    'id'                => 'RzpMakerRoleId',
                                    'name'              => 'Maker',
                                    'description'       => 'Manager of roles',
                                    'org_id'            => '100000razorpay'
                                ]
                            ]
                        ]
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  100,
                        'max_amount'    =>  1000,
                        'steps'         =>  []
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'condition'     =>  null,
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
                        'steps'         =>  []
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
                        'min_amount'    =>  1000,
                        'max_amount'    =>  null,
                    ],
                    [
                        'min_amount'    =>  100,
                        'max_amount'    =>  1000,
                    ],
                    [
                        'min_amount'    =>  0,
                        'max_amount'    =>  100,
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
];
