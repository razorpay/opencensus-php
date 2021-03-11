<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Workflow;

return [
    'testCreateWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => [
                'name' => 'Test workflow',
            ],
        ],
        'response' => [
            'content' => [
                'name'   => "Test workflow",
                'levels' => [
                    [
                        'op_type' => 'or',
                        'level'   => 1
                    ]
                ]
            ]
        ],
    ],
    'testDeleteWorkflow' => [
        'request' => [
            'method'  => 'DELETE',
            'url'     => '/workflows/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'name' => 'Test workflow',
            ]
        ]
    ],
    'testCreateWorkflowWithPermissionWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => [
                'name' => 'Test workflow',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One of the permissions already has' .
                                     ' a workflow defined',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS,
        ],
    ],
    'testDeleteWorkflowProgress' => [
        'request' => [
            'method'  => 'DELETE',
            'url'     => '/workflows/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Updating or Deleting a workflow is not allowed when there are open actions',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED,
        ],
    ],
    'testEditWorkflow' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/workflows/%s',
            'content' => [
                'name' => 'editing workflow',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'editing workflow',
            ],
        ],
    ],
    'testEditWorkflowInProgress' => [
        'request' => [
            'method' => 'PUT',
            'url'    => '/workflows/%s',
            'content' => [
                'name' => 'editing workflow',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED,
        ],
    ],
    'testGetWorkflow' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/workflows/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'levels' => [
                    [
                        'op_type' => 'or',
                        'level'   => 1
                    ],
                ],
            ],
        ],
    ],
    'testWorkflowGetMultiple' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/workflows',
            'content'   => [],
        ],
        'response'  => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'id'    => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID
                    ]
                ]
            ]
        ]
    ],
    'testCreateWorkflowWithCreatePayoutPermissionWithoutMerchantId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/workflows',
            'content' => [
                'name' => 'Test workflow',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant id should be passed for this permission',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PASSED,
        ],
    ],

    'testWorkflowStateCallbackFromNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/state/callback',
            'content' => [
                "Id"            => "FSYqHROoUij6TF",
                "GroupName"     =>  "ABC",
                "Name"          => "Owner_Approval",
                "Rules"         => [
                    "ActorPropertyKey"      => "role",
                    "ActorPropertyValue"    => "owner",
                ],
                "Status"        => "created",
                "Type"          => "checker",
                "WorkflowId"    => "FSYpen1s24sSbs",
            ],
        ],
        'response' => [
            'content' => [
                "workflow_id"       => "FSYpen1s24sSbs",
                "merchant_id"       => "10000000000000",
                "org_id"            => "100000razorpay",
                "actor_type_key"    => "role",
                "actor_type_value"  => "owner",
                "state_id"          => "FSYqHROoUij6TF",
                "state_name"        => "Owner_Approval",
                "status"            => "created",
                "group_name"        => "ABC",
                "type"              => "checker"
            ],
        ],
    ],

    'testCreateWorkflowConfigNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/configs/',
            'content' => [
                "config" => [
                    "template" => [
                        "type" => "approval",
                        "state_transitions" => [
                            "START_STATE" => [
                                "current_state" => "START_STATE",
                                "next_states" => [
                                    "0_1k_workflow",
                                    "1k_10k_workflow",
                                    "10k_10Cr_workflow"
                                ]
                            ],
                            "0_1k_workflow" => [
                                "current_state" => "0_1k_workflow",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ],
                            "1k_10k_workflow" => [
                                "current_state" => "1k_10k_workflow",
                                "next_states" => [
                                    "FL1_Approval"
                                ]
                            ],
                            "FL1_Approval" => [
                                "current_state" => "FL1_Approval",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ],
                            "10k_10Cr_workflow" => [
                                "current_state" => "10k_10Cr_workflow",
                                "next_states" => [
                                    "Owner_Approval"
                                ]
                            ],
                            "Owner_Approval" => [
                                "current_state" => "Owner_Approval",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ]
                        ],
                        "states_data" => [
                            "0_1k_workflow" => [
                                "name" => "0_1k_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 1,
                                    "max" => 1000
                                ]
                            ],
                            "1k_10k_workflow" => [
                                "name" => "1k_10k_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 1000,
                                    "max" => 10000
                                ]
                            ],
                            "10k_10Cr_workflow" => [
                                "name" => "10k_10Cr_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 10000,
                                    "max" => 100000000
                                ]
                            ],
                            "FL1_Approval" => [
                                "name" => "FL1_Approval",
                                "group_name" => "ABC",
                                "type" => "checker",
                                "rules" => [
                                    "actor_property_key" => "role",
                                    "actor_property_value" => "fl1",
                                    "count" => 2
                                ],
                                "callbacks" => [
                                    "status" => [
                                        "in" => [
                                            "created",
                                            "processed"
                                        ]
                                    ]
                                ]
                            ],
                            "Owner_Approval" => [
                                "name" => "Owner_Approval",
                                "group_name" => "ABC",
                                "type" => "checker",
                                "rules" => [
                                    "actor_property_key" => "role",
                                    "actor_property_value" => "owner",
                                    "count" => 1
                                ],
                                "callbacks" => [
                                    "status" => [
                                        "in" => [
                                            "created",
                                            "processed"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "allowed_actions" => [
                            "checker" => [
                                "actions" => [
                                    "approved",
                                    "rejected"
                                ]
                            ],
                            "admin" => [
                                "actions" => [
                                    "update_data",
                                    "rejected"
                                ]
                            ],
                            "rx_live" => [
                                "actions" => [
                                    "rejected"
                                ]
                            ],
                            "rx-test" => [
                                "actions" => [
                                    "rejected"
                                ]
                            ]
                        ],
                        "meta" => [
                            "domain" => "payouts",
                            "task_list_name" => "payouts-approval"
                        ]
                    ],
                    "version" => "1",
                    "type" => "payout-approval",
                    "name" => "10000000000000 - Payout approval workflow",
                    "service" => "rx_live",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "context" => [
                        "aa" => "test context"
                    ],
                    "enabled" => "true"
                ]
            ],
        ],
        'response' => [
            'content' => [
                "id" =>  "FQE6Xw4ZpoM21X",
                "name" =>  "10000000000000 - Payout approval workflow",
                "template" =>  [
                    "state_transitions" =>  [
                        "0_1k_workflow" =>  [
                            "current_state" =>  "0_1k_workflow",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "current_state" =>  "10k_10Cr_workflow",
                            "next_states" =>  [
                                "Owner_Approval"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "current_state" =>  "1k_10k_workflow",
                            "next_states" =>  [
                                "FL1_Approval",
                                "FL4_Approval"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "current_state" =>  "Admin_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "And_1_result" =>  [
                            "current_state" =>  "And_1_result",
                            "next_states" =>  [
                                "FL2_Approval",
                                "Admin_Approval"
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "current_state" =>  "FL1_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "current_state" =>  "FL2_Approval",
                            "next_states" =>  [
                                "FL3_Approval"
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "current_state" =>  "FL3_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "current_state" =>  "FL4_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "current_state" =>  "Owner_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "START_STATE" =>  [
                            "current_state" =>  "START_STATE",
                            "next_states" =>  [
                                "0_1k_workflow",
                                "1k_10k_workflow",
                                "10k_10Cr_workflow"
                            ]
                        ]
                    ],
                    "states_data" =>  [
                        "0_1k_workflow" =>  [
                            "name" =>  "0_1k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1",
                                "max" =>  "1000",
                                "key" =>  "amount"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "name" =>  "10k_10Cr_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "10000",
                                "max" =>  "100000000",
                                "key" =>  "amount"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "name" =>  "1k_10k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1000",
                                "max" =>  "10000",
                                "key" =>  "amount"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "name" =>  "Admin_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "admin",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "And_1_result" =>  [
                            "name" =>  "And_1_result",
                            "group" =>  "ABC",
                            "type" =>  "merge_states",
                            "rules" =>  [
                                "states" =>  [
                                    "FL1_Approval",
                                    "FL4_Approval"
                                ]
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "name" =>  "FL1_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl1",
                                "count" =>  2
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "name" =>  "FL2_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl2",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "name" =>  "FL3_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl3",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "name" =>  "FL4_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl4",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "name" =>  "Owner_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "owner",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "allowed_actions" =>  [
                        "approved",
                        "rejected"
                    ],
                    "meta" =>  [
                        "domain" =>  "payouts",
                        "task_list_name" =>  "payouts-approval",
                        "workflow_expire_time" =>  "3000"
                    ],
                    "type" =>  "approval"
                ],
                "type" =>  "payout-approval",
                "version" =>  1,
                "owner_id" =>  "10000000000000",
                "owner_type" =>  "merchant",
                "context" =>  [
                    "aa" =>  "test context"
                ],
                "enabled" =>  "true",
                "service" =>  "rx_live",
                "org_id" =>  "100000razorpay",
                "created_at" =>  "1597317215"
            ],
        ],
    ],

    'testCreateWorkflowConfigWithPendingPayoutNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/wf-service/configs/',
            'content' => [
                "config" => [
                    "template" => [
                        "type" => "approval",
                        "state_transitions" => [
                            "START_STATE" => [
                                "current_state" => "START_STATE",
                                "next_states" => [
                                    "0_1k_workflow",
                                    "1k_10k_workflow",
                                    "10k_10Cr_workflow"
                                ]
                            ],
                            "0_1k_workflow" => [
                                "current_state" => "0_1k_workflow",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ],
                            "1k_10k_workflow" => [
                                "current_state" => "1k_10k_workflow",
                                "next_states" => [
                                    "FL1_Approval"
                                ]
                            ],
                            "FL1_Approval" => [
                                "current_state" => "FL1_Approval",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ],
                            "10k_10Cr_workflow" => [
                                "current_state" => "10k_10Cr_workflow",
                                "next_states" => [
                                    "Owner_Approval"
                                ]
                            ],
                            "Owner_Approval" => [
                                "current_state" => "Owner_Approval",
                                "next_states" => [
                                    "END_STATE"
                                ]
                            ]
                        ],
                        "states_data" => [
                            "0_1k_workflow" => [
                                "name" => "0_1k_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 1,
                                    "max" => 1000
                                ]
                            ],
                            "1k_10k_workflow" => [
                                "name" => "1k_10k_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 1000,
                                    "max" => 10000
                                ]
                            ],
                            "10k_10Cr_workflow" => [
                                "name" => "10k_10Cr_workflow",
                                "group_name" => "ABC",
                                "type" => "between",
                                "rules" => [
                                    "key" => "amount",
                                    "min" => 10000,
                                    "max" => 100000000
                                ]
                            ],
                            "FL1_Approval" => [
                                "name" => "FL1_Approval",
                                "group_name" => "ABC",
                                "type" => "checker",
                                "rules" => [
                                    "actor_property_key" => "role",
                                    "actor_property_value" => "fl1",
                                    "count" => 2
                                ],
                                "callbacks" => [
                                    "status" => [
                                        "in" => [
                                            "created",
                                            "processed"
                                        ]
                                    ]
                                ]
                            ],
                            "Owner_Approval" => [
                                "name" => "Owner_Approval",
                                "group_name" => "ABC",
                                "type" => "checker",
                                "rules" => [
                                    "actor_property_key" => "role",
                                    "actor_property_value" => "owner",
                                    "count" => 1
                                ],
                                "callbacks" => [
                                    "status" => [
                                        "in" => [
                                            "created",
                                            "processed"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "allowed_actions" => [
                            "checker" => [
                                "actions" => [
                                    "approved",
                                    "rejected"
                                ]
                            ],
                            "admin" => [
                                "actions" => [
                                    "update_data",
                                    "rejected"
                                ]
                            ],
                            "rx_live" => [
                                "actions" => [
                                    "rejected"
                                ]
                            ],
                            "rx_test" => [
                                "actions" => [
                                    "rejected"
                                ]
                            ]
                        ],
                        "meta" => [
                            "domain" => "payouts",
                            "task_list_name" => "payouts-approval"
                        ]
                    ],
                    "version" => "1",
                    "type" => "payout-approval",
                    "name" => "10000000000000 - Payout approval workflow",
                    "service" => "rx_live",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "context" => [
                        "aa" => "test context"
                    ],
                    "enabled" => "true"
                ]
            ],
        ],
        'response' => [

        ]
    ],

    'testUpdateWorkflowConfigNWFS' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/wf-service/configs/',
            'content' => [
                "id" =>  "FQE6Xw4ZpoM21X",
                "name" =>  "10000000000000 - Payout approval workflow",
                "service" =>  "rx_live",
                "owner_id" =>  "10000000000000",
                "owner_type" =>  "merchant",
                "enabled" =>  "false",
            ],
        ],
        'response' => [
            'content' => [
                "id" =>  "FQE6Xw4ZpoM21X",
                "name" =>  "10000000000000 - Payout approval workflow",
                "template" =>  [
                    "state_transitions" =>  [
                        "0_1k_workflow" =>  [
                            "current_state" =>  "0_1k_workflow",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "current_state" =>  "10k_10Cr_workflow",
                            "next_states" =>  [
                                "Owner_Approval"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "current_state" =>  "1k_10k_workflow",
                            "next_states" =>  [
                                "FL1_Approval",
                                "FL4_Approval"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "current_state" =>  "Admin_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "And_1_result" =>  [
                            "current_state" =>  "And_1_result",
                            "next_states" =>  [
                                "FL2_Approval",
                                "Admin_Approval"
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "current_state" =>  "FL1_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "current_state" =>  "FL2_Approval",
                            "next_states" =>  [
                                "FL3_Approval"
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "current_state" =>  "FL3_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "current_state" =>  "FL4_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "current_state" =>  "Owner_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "START_STATE" =>  [
                            "current_state" =>  "START_STATE",
                            "next_states" =>  [
                                "0_1k_workflow",
                                "1k_10k_workflow",
                                "10k_10Cr_workflow"
                            ]
                        ]
                    ],
                    "states_data" =>  [
                        "0_1k_workflow" =>  [
                            "name" =>  "0_1k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1",
                                "max" =>  "1000",
                                "key" =>  "amount"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "name" =>  "10k_10Cr_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "10000",
                                "max" =>  "100000000",
                                "key" =>  "amount"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "name" =>  "1k_10k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1000",
                                "max" =>  "10000",
                                "key" =>  "amount"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "name" =>  "Admin_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "admin",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "And_1_result" =>  [
                            "name" =>  "And_1_result",
                            "group" =>  "ABC",
                            "type" =>  "merge_states",
                            "rules" =>  [
                                "states" =>  [
                                    "FL1_Approval",
                                    "FL4_Approval"
                                ]
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "name" =>  "FL1_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl1",
                                "count" =>  2
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "name" =>  "FL2_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl2",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "name" =>  "FL3_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl3",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "name" =>  "FL4_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl4",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "name" =>  "Owner_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "owner",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "allowed_actions" =>  [
                        "approved",
                        "rejected"
                    ],
                    "meta" =>  [
                        "domain" =>  "payouts",
                        "task_list_name" =>  "payouts-approval",
                        "workflow_expire_time" =>  "3000"
                    ],
                    "type" =>  "approval"
                ],
                "type" =>  "payout-approval",
                "version" =>  1,
                "owner_id" =>  "10000000000000",
                "owner_type" =>  "merchant",
                "context" =>  [
                    "aa" =>  "test context"
                ],
                "enabled" =>  "false",
                "service" =>  "rx_live",
                "org_id" =>  "100000razorpay",
                "created_at" =>  "1597317215"
            ],
        ],
    ],

    'testUpdateWorkflowConfigWithPendingPayoutsNWFS' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/wf-service/configs/',
            'content' => [
                "id" =>  "FQE6Xw4ZpoM21X",
                "name" =>  "10000000000000 - Payout approval workflow",
                "service" =>  "rx_live",
                "owner_id" =>  "10000000000000",
                "owner_type" =>  "merchant",
                "enabled" =>  "false"
            ],
        ],
        'response' => [

        ]

    ],

    'testGetWorkflowConfigWFSWithPermission' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/wf-service/configs/{id}',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "id" =>  "FQE6Xw4ZpoM21X",
                "name" =>  "10000000000000 - Payout approval workflow",
                "template" =>  [
                    "state_transitions" =>  [
                        "0_1k_workflow" =>  [
                            "current_state" =>  "0_1k_workflow",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "current_state" =>  "10k_10Cr_workflow",
                            "next_states" =>  [
                                "Owner_Approval"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "current_state" =>  "1k_10k_workflow",
                            "next_states" =>  [
                                "FL1_Approval",
                                "FL4_Approval"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "current_state" =>  "Admin_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "And_1_result" =>  [
                            "current_state" =>  "And_1_result",
                            "next_states" =>  [
                                "FL2_Approval",
                                "Admin_Approval"
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "current_state" =>  "FL1_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "current_state" =>  "FL2_Approval",
                            "next_states" =>  [
                                "FL3_Approval"
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "current_state" =>  "FL3_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "current_state" =>  "FL4_Approval",
                            "next_states" =>  [
                                "And_1_result"
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "current_state" =>  "Owner_Approval",
                            "next_states" =>  [
                                "END_STATE"
                            ]
                        ],
                        "START_STATE" =>  [
                            "current_state" =>  "START_STATE",
                            "next_states" =>  [
                                "0_1k_workflow",
                                "1k_10k_workflow",
                                "10k_10Cr_workflow"
                            ]
                        ]
                    ],
                    "states_data" =>  [
                        "0_1k_workflow" =>  [
                            "name" =>  "0_1k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1",
                                "max" =>  "1000",
                                "key" =>  "amount"
                            ]
                        ],
                        "10k_10Cr_workflow" =>  [
                            "name" =>  "10k_10Cr_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "10000",
                                "max" =>  "100000000",
                                "key" =>  "amount"
                            ]
                        ],
                        "1k_10k_workflow" =>  [
                            "name" =>  "1k_10k_workflow",
                            "group" =>  "ABC",
                            "type" =>  "between",
                            "rules" =>  [
                                "min" =>  "1000",
                                "max" =>  "10000",
                                "key" =>  "amount"
                            ]
                        ],
                        "Admin_Approval" =>  [
                            "name" =>  "Admin_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "admin",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "And_1_result" =>  [
                            "name" =>  "And_1_result",
                            "group" =>  "ABC",
                            "type" =>  "merge_states",
                            "rules" =>  [
                                "states" =>  [
                                    "FL1_Approval",
                                    "FL4_Approval"
                                ]
                            ]
                        ],
                        "FL1_Approval" =>  [
                            "name" =>  "FL1_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl1",
                                "count" =>  2
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL2_Approval" =>  [
                            "name" =>  "FL2_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl2",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL3_Approval" =>  [
                            "name" =>  "FL3_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl3",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "FL4_Approval" =>  [
                            "name" =>  "FL4_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "fl4",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ],
                        "Owner_Approval" =>  [
                            "name" =>  "Owner_Approval",
                            "group" =>  "ABC",
                            "type" =>  "checker",
                            "rules" =>  [
                                "actor_property_key" =>  "role",
                                "actor_property_value" =>  "owner",
                                "count" =>  1
                            ],
                            "callbacks" =>  [
                                "status" =>  [
                                    "in" =>  [
                                        "created",
                                        "processed"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "allowed_actions" =>  [
                        "approved",
                        "rejected"
                    ],
                    "meta" =>  [
                        "domain" =>  "payouts",
                        "task_list_name" =>  "payouts-approval",
                        "workflow_expire_time" =>  "3000"
                    ],
                    "type" =>  "approval"
                ],
                "type" =>  "payout-approval",
                "version" =>  1,
                "owner_id" =>  "10000000000000",
                "owner_type" =>  "merchant",
                "context" =>  [
                    "aa" =>  "test context"
                ],
                "enabled" =>  "false",
                "service" =>  "rx_live",
                "org_id" =>  "100000razorpay",
                "created_at" =>  "1597317215"
            ],
        ],
    ],

    'testGetWorkflowConfigWFSWithoutPermission' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/wf-service/configs/{id}',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

];
