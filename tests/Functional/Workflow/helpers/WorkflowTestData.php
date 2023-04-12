<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Workflow\Service\Config\Entity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
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

    'testCreateWorkflowWithTemplate' => [
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

    'createWorkflowPayoutAmountRules' => [
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
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  0,
                        'max_amount'    =>  100000
                    ],
                    [
                        'merchant_id'   =>  '10000000000000',
                        'min_amount'    =>  100000,
                        'max_amount'    =>  null
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

    'testWorkflowStateCallbackFromPayouts' => [
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

    'testWorkflowStateUpdateCallbackFromPayouts' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/wf-service/state/FSYqHROoUij6TF/callback',
            'content' => [
                "Status"     => "processed",
                "WorkflowId" => "FSYpen1s24sSbs",
            ],
            'headers' => [
                'x-creator-id' => 'ABCDFBJDFF',
                'X-Razorpay-Account' => 'acc_10000000000000'
            ]
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
                "status"            => "processed",
                "group_name"        => "ABC",
                "type"              => "checker"
            ],
        ],
    ],

    'testWorkflowStateCallbackFromNWFSToPS' => [
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
            'headers' => [
                'x-creator-id' => 'ABCDFBJDFF',
                'X-Razorpay-Account' => 'acc_10000000000000'
            ]
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

    'testWorkflowStateUpdateCallbackFromNWFSToPS' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/wf-service/state/FSYqHROoUij6TF/callback',
            'content' => [
                "Status"     => "processed",
                "WorkflowId" => "FSYpen1s24sSbs",
            ],
            'headers' => [
                'x-creator-id' => 'ABCDFBJDFF',
                'X-Razorpay-Account' => 'acc_10000000000000'
            ]
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
                "status"            => "processed",
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


    'testCreateWorkflowConfigWithAccountNumber' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'create_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testCreateWorkflowConfig' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'create_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testCreateWorkflowConfigViaInternalRoute' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/internal/workflow/config',
            'content' => [
                'action' => 'workflow_config_create_internal'
            ],
            'server' => [
                'HTTP_X-Merchant-Id' => '10000000000000',
            ]
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

    'testCreateWorkflowConfigViaPrivateRoute' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/internal/workflow/config',
            'content' => [
                'action' => 'workflow_config_create_internal'
            ],
            'server' => [
                'HTTP_X-Merchant-Id' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateWorkflowConfigViaInternalRouteWithFeatureAlreadyEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/internal/workflow/config',
            'content' => [
                'action' => 'workflow_config_create_internal'
            ],
            'server' => [
                'HTTP_X-Merchant-Id' => '10000000000000',
            ]
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

    'testCreateICICIWorkflowConfigInAdminAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/workflow/config',
            'content' => [
                'config_type' => 'icici-payouts-approval',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testBulkCreateWorkflowConfigInAdminAuthWithFeatureAlreadyEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/workflow/config/bulk',
            'content' => [
                Entity::MERCHANT_IDS => ['10000000000000']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                "total_mids" =>  1,
                "success_mids" =>  [],
                "failed_mids" =>  ['10000000000000']
            ]
        ]
    ],

    'testBulkCreateWorkflowConfigInAdminAuthWithInvalidInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/workflow/config/bulk',
            'content' => [
                Entity::MERCHANT_IDS => ['10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000', '10000000000000']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant ids may not have more than 50 items.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateWorkflowConfigWithAccountNumber' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'update_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testUpdateWorkflowConfigWithPendingPayoutLinks' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'update_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testUpdateWorkflowConfigWithPendingPayouts' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'update_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testUpdateWorkflowConfig' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'update_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testUpdateICICIWorkflowConfigInAdminAuth' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/admin/workflow/config',
            'content' => [
                'config_type' => 'icici-payouts-approval',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
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

    'testDeleteWorkflowConfigWithAccountNumber' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'delete_workflow_config',
                'account_numbers' => ['2224440041626909']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteWorkflowConfigWithPendingPayoutLinks' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'delete_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteWorkflowConfig' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/workflow/config',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'action' => 'delete_workflow_config',
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteICICIWorkflowConfigInAdminAuth' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/admin/workflow/config',
            'content' => [
                'account_numbers' => ['2224440041626900']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
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

    'testCreateWorkflowConfigWithPendingPayoutLinks' => [
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
                'template' => []
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

    'testGetWorkflowConfigWFSFromAdminDashWithPermission' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/wf-service-admin/configs/{id}',
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

    'testGetWorkflowConfigWFSFromAdminDashWithoutPermission' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/wf-service-admin/configs/{id}',
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
    'testGetWorkflowConfigWFSFromXDashboardWithPermission' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
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
    'testWorkflowSyncFlowForConfigEdit' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/admin-workflows/rules/payout_amount',
            'content' => [
                "workflows" => [
                    [
                        "name" => "Test workflow",
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
                            "name" => "Test workflow",
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
    'workflowSyncFlowForConfigCreation' => [
        'template_level_1' => [
            'name'   => 'Test workflow',
            'levels' => [
                [
                    'level'   => 1,
                    'op_type' => 'or',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::MANAGER_ROLE,
                        ]
                    ],
                ],
                [
                    'level'   => 2,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ],
        'template_level_2' => [
            'name'   => 'Test workflow 1',
            'levels' => [
                [
                    'level'   => 1,
                    'op_type' => 'or',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::MANAGER_ROLE,
                        ]
                    ],
                ]
            ]
        ],
        'expected_config' => array (
            'template' =>
                array (
                    'type' => 'approval',
                    'state_transitions' =>
                        array (
                            'START_STATE' =>
                                array (
                                    'current_state' => 'START_STATE',
                                    'next_states' =>
                                        array (
                                            0 => '1-100000_workflow',
                                            1 => '100000-20000000000_workflow',
                                        ),
                                ),
                            '1-100000_workflow' =>
                                array (
                                    'current_state' => '1-100000_workflow',
                                    'next_states' =>
                                        array (
                                            0 => 'Admin_0_0_Approval',
                                        ),
                                ),
                            'Admin_0_0_Approval' =>
                                array (
                                    'current_state' => 'Admin_0_0_Approval',
                                    'next_states' =>
                                        array (
                                            0 => 'SuperAdmin_0_1_Approval',
                                        ),
                                ),
                            'SuperAdmin_0_1_Approval' =>
                                array (
                                    'current_state' => 'SuperAdmin_0_1_Approval',
                                    'next_states' =>
                                        array (
                                            0 => 'END_STATE',
                                        ),
                                ),
                            '100000-20000000000_workflow' =>
                                array (
                                    'current_state' => '100000-20000000000_workflow',
                                    'next_states' =>
                                        array (
                                            0 => 'Admin_1_0_Approval',
                                        ),
                                ),
                            'Admin_1_0_Approval' =>
                                array (
                                    'current_state' => 'Admin_1_0_Approval',
                                    'next_states' =>
                                        array (
                                            0 => 'END_STATE',
                                        ),
                                ),
                        ),
                    'states_data' =>
                        array (
                            '1-100000_workflow' =>
                                array (
                                    'name' => '1-100000_workflow',
                                    'group_name' => '0',
                                    'type' => 'between',
                                    'rules' =>
                                        array (
                                            'key' => 'amount',
                                            'min' => '1',
                                            'max' => '100000',
                                        ),
                                ),
                            'Admin_0_0_Approval' =>
                                array (
                                    'name' => 'Admin_0_0_Approval',
                                    'group_name' => '1',
                                    'type' => 'checker',
                                    'rules' =>
                                        array (
                                            'actor_property_key' => 'role',
                                            'actor_property_value' => 'admin',
                                            'count' => 1,
                                        ),
                                    'callbacks' =>
                                        array (
                                            'status' =>
                                                array (
                                                    'in' =>
                                                        array (
                                                            0 => 'created',
                                                            1 => 'processed',
                                                        ),
                                                ),
                                        ),
                                ),
                            'SuperAdmin_0_1_Approval' =>
                                array (
                                    'name' => 'SuperAdmin_0_1_Approval',
                                    'group_name' => '2',
                                    'type' => 'checker',
                                    'rules' =>
                                        array (
                                            'actor_property_key' => 'role',
                                            'actor_property_value' => 'superadmin',
                                            'count' => 1,
                                        ),
                                    'callbacks' =>
                                        array (
                                            'status' =>
                                                array (
                                                    'in' =>
                                                        array (
                                                            0 => 'created',
                                                            1 => 'processed',
                                                        ),
                                                ),
                                        ),
                                ),
                            '100000-20000000000_workflow' =>
                                array (
                                    'name' => '100000-20000000000_workflow',
                                    'group_name' => '0',
                                    'type' => 'between',
                                    'rules' =>
                                        array (
                                            'key' => 'amount',
                                            'min' => '100000',
                                            'max' => '20000000000',
                                        ),
                                ),
                            'Admin_1_0_Approval' =>
                                array (
                                    'name' => 'Admin_1_0_Approval',
                                    'group_name' => '1',
                                    'type' => 'checker',
                                    'rules' =>
                                        array (
                                            'actor_property_key' => 'role',
                                            'actor_property_value' => 'admin',
                                            'count' => 1,
                                        ),
                                    'callbacks' =>
                                        array (
                                            'status' =>
                                                array (
                                                    'in' =>
                                                        array (
                                                            0 => 'created',
                                                            1 => 'processed',
                                                        ),
                                                ),
                                        ),
                                ),
                        ),
                    'allowed_actions' =>
                        array (
                            'admin' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'update_data',
                                            1 => 'rejected',
                                        ),
                                ),
                            'user' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'approved',
                                            1 => 'rejected',
                                        ),
                                ),
                            'rx_live' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'rejected',
                                        ),
                                ),
                        ),
                    'meta' =>
                        array (
                            'domain' => 'payouts',
                            'task_list_name' => 'payouts-approval',
                        ),
                ),
            'version' => 1,
            'type' => 'payout-approval',
            'name' => '10000000000000 - Payout approval workflow',
            'service' => 'rx_live',
            'owner_id' => '10000000000000',
            'owner_type' => 'merchant',
            'org_id' => '100000razorpay',
            'enabled' => 'true',
        ),
    ],
    'workflowSyncFlowForConfigEdit' => [
        'expected_config' => array (
            'name' => '10000000000000 - Payout approval workflow',
            'template' =>
                array (
                    'state_transitions' =>
                        array (
                            '1-10_workflow' =>
                                array (
                                    'current_state' => '1-10_workflow',
                                    'next_states' =>
                                        array (
                                            0 => 'Maker_0_0_Approval',
                                        ),
                                ),
                            'Maker_0_0_Approval' =>
                                array (
                                    'current_state' => 'Maker_0_0_Approval',
                                    'next_states' =>
                                        array (
                                            0 => 'END_STATE',
                                        ),
                                ),
                            'START_STATE' =>
                                array (
                                    'current_state' => 'START_STATE',
                                    'next_states' =>
                                        array (
                                            0 => '1-10_workflow',
                                        ),
                                ),
                        ),
                    'states_data' =>
                        array (
                            '1-10_workflow' =>
                                array (
                                    'name' => '1-10_workflow',
                                    'group_name' => '0',
                                    'type' => 'between',
                                    'rules' =>
                                        array (
                                            'min' => '1',
                                            'max' => '10',
                                            'key' => 'amount',
                                        ),
                                ),
                            'Maker_0_0_Approval' =>
                                array (
                                    'name' => 'Maker_0_0_Approval',
                                    'group_name' => '1',
                                    'type' => 'checker',
                                    'rules' =>
                                        array (
                                            'actor_property_key' => 'role',
                                            'actor_property_value' => 'maker',
                                            'count' => 1,
                                        ),
                                    'callbacks' =>
                                        array (
                                            'status' =>
                                                array (
                                                    'in' =>
                                                        array (
                                                            0 => 'created',
                                                            1 => 'processed',
                                                        ),
                                                ),
                                        ),
                                ),
                        ),
                    'allowed_actions' =>
                        array (
                            'admin' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'update_data',
                                            1 => 'rejected',
                                        ),
                                ),
                            'rx_live' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'rejected',
                                        ),
                                ),
                            'user' =>
                                array (
                                    'actions' =>
                                        array (
                                            0 => 'approved',
                                            1 => 'rejected',
                                        ),
                                ),
                        ),
                    'meta' =>
                        array (
                            'domain' => 'payouts',
                            'task_list_name' => 'payouts-approval',
                        ),
                    'type' => 'approval',
                ),
            'type' => 'payout-approval',
            'version' => 1,
            'owner_id' => '10000000000000',
            'owner_type' => 'merchant',
            'enabled' => 'true',
            'service' => 'rx_live',
            'org_id' => '100000razorpay',
        ),
    ],

];
