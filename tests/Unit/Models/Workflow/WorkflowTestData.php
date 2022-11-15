<?php

return [
    'testMigrationRouteForSimpleWorkflow' => [
        'amount_rules' => collect([
            collect([
                'id' => 37,
                'merchant_id' => "10000000000000",
                'condition'   => null,
                'min_amount'  => 0,
                'max_amount'  => 100000,
                'workflow_id' => "HNe4QVYjpAUk8W",
                'created_at'  => 1623827811,
                'updated_at'  => 1623827811,
                'deleted_at'  => null,
                'permission_id' => "HKZ69hGOOkTGbg",
                'workflow' => [
                    'steps'         =>    [
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
            ]),
            collect([
                'id' => 37,
                'merchant_id' => "10000000000000",
                'condition'   => null,
                'min_amount'  => 100000,
                'max_amount'  => null,
                'workflow_id' => "HNe4RNmaWm5SbB",
                'created_at'  => 1623827811,
                'updated_at'  => 1623827811,
                'deleted_at'  => null,
                'permission_id' => "HKZ69hGOOkTGbg",
                'workflow' => [
                    'steps'         =>   [
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
                    ]
                ]
            ])
        ]),
        'new_config' => [
            'config' => array (
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
                                                0 => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                            ),
                                    ),
                                'Checker_role_RzpChekrRoleId_0_0_Approval' =>
                                    array (
                                        'current_state' => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                        'next_states' =>
                                            array (
                                                0 => 'Maker_role_RzpMakerRoleId_0_1_Approval',
                                            ),
                                    ),
                                'Maker_role_RzpMakerRoleId_0_1_Approval' =>
                                    array (
                                        'current_state' => 'Maker_role_RzpMakerRoleId_0_1_Approval',
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
                                                0 => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
                                            ),
                                    ),
                                'SuperAdmin_role_RzpAdminRoleId_1_0_Approval' =>
                                    array (
                                        'current_state' => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
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
                                                'min' => 1,
                                                'max' => 100000,
                                            ),
                                    ),
                                'Checker_role_RzpChekrRoleId_0_0_Approval' =>
                                    array (
                                        'name' => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                        'group_name' => '1',
                                        'type' => 'checker',
                                        'rules' =>
                                            array (
                                                'actor_property_key' => 'role',
                                                'actor_property_value' => 'checker',
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
                                'Maker_role_RzpMakerRoleId_0_1_Approval' =>
                                    array (
                                        'name' => 'Maker_role_RzpMakerRoleId_0_1_Approval',
                                        'group_name' => '2',
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
                                '100000-20000000000_workflow' =>
                                    array (
                                        'name' => '100000-20000000000_workflow',
                                        'group_name' => '0',
                                        'type' => 'between',
                                        'rules' =>
                                            array (
                                                'key' => 'amount',
                                                'min' => 100000,
                                                'max' => 20000000000,
                                            ),
                                    ),
                                'SuperAdmin_role_RzpAdminRoleId_1_0_Approval' =>
                                    array (
                                        'name' => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
                                        'group_name' => '1',
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
                                'owner' =>
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
                'version' => '1',
                'type' => 'payout-approval',
                'name' => '10000000000000 - Payout approval workflow',
                'service' => 'rx_live',
                'owner_id' => '10000000000000',
                'owner_type' => 'merchant',
                'org_id' => '100000razorpay',
                'enabled' => 'true',
            )
        ]
    ],
    'testMigrationRouteForComplexWorkflow' => [
        'amount_rules' => collect([
            collect([
                'id' => 37,
                'merchant_id' => "10000000000000",
                'condition'   => null,
                'min_amount'  => 0,
                'max_amount'  => 100000,
                'workflow_id' => "HNe4QVYjpAUk8W",
                'created_at'  => 1623827811,
                'updated_at'  => 1623827811,
                'deleted_at'  => null,
                'permission_id' => "HKZ69hGOOkTGbg",
                'workflow' => [
                    'steps'         =>    [
                        [
                            'role_id'           => 'role_RzpChekrRoleId',
                            'workflow_id'       => 'workflow_workflowId1000',
                            'reviewer_count'    =>  1,
                            'op_type'           => 'and',
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
                            'level'             =>  1,
                            'role'              => [
                                'id'                => 'role_RzpMakerRoleId',
                                'name'              => 'Maker',
                                'description'       => 'Manager of roles',
                                'org_id'            => 'org_100000razorpay'
                            ]
                        ]
                    ]
                ]
            ]),
            collect([
                'id' => 37,
                'merchant_id' => "10000000000000",
                'condition'   => null,
                'min_amount'  => 100000,
                'max_amount'  => null,
                'workflow_id' => "HNe4RNmaWm5SbB",
                'created_at'  => 1623827811,
                'updated_at'  => 1623827811,
                'deleted_at'  => null,
                'permission_id' => "HKZ69hGOOkTGbg",
                'workflow' => [
                    'steps'         =>   [
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
                            'role_id'           =>  'role_FinanceL1',
                            'workflow_id'       =>  'workflow_workflowId1001',
                            'reviewer_count'    =>  1,
                            'op_type'           =>  'or',
                            'level'             =>  1,
                            'role'              =>  [
                                'id'                => 'role_FinanceL1',
                                'name'              => 'Finance_L1',
                                'description'       => 'fl1 of roles',
                                'org_id'            => 'org_100000razorpay',
                            ]
                        ],
                        [
                            'role_id'           =>  'role_FinanceL2',
                            'workflow_id'       =>  'workflow_workflowId1002',
                            'reviewer_count'    =>  2,
                            'op_type'           =>  'and',
                            'level'             =>  2,
                            'role'              =>  [
                                'id'                => 'role_FinanceL2',
                                'name'              => 'Finance_L2',
                                'description'       => 'fl2 of roles',
                                'org_id'            => 'org_100000razorpay',
                            ]
                        ]
                    ]
                ]
            ])
        ]),
        'new_config' => [
            'config' =>
                array (
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
                                                    0 => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                                    1 => 'Maker_role_RzpMakerRoleId_0_0_Approval',
                                                ),
                                        ),
                                    'Checker_role_RzpChekrRoleId_0_0_Approval' =>
                                        array (
                                            'current_state' => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                            'next_states' =>
                                                array (
                                                    0 => 'And_0_0_Result',
                                                ),
                                        ),
                                    'Maker_role_RzpMakerRoleId_0_0_Approval' =>
                                        array (
                                            'current_state' => 'Maker_role_RzpMakerRoleId_0_0_Approval',
                                            'next_states' =>
                                                array (
                                                    0 => 'And_0_0_Result',
                                                ),
                                        ),
                                    'And_0_0_Result' =>
                                        array (
                                            'current_state' => 'And_0_0_Result',
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
                                                    0 => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
                                                    1 => 'Finance_L1_role_FinanceL1_1_0_Approval',
                                                ),
                                        ),
                                    'SuperAdmin_role_RzpAdminRoleId_1_0_Approval' =>
                                        array (
                                            'current_state' => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
                                            'next_states' =>
                                                array (
                                                    0 => 'Finance_L2_role_FinanceL2_1_1_Approval',
                                                ),
                                        ),
                                    'Finance_L1_role_FinanceL1_1_0_Approval' =>
                                        array (
                                            'current_state' => 'Finance_L1_role_FinanceL1_1_0_Approval',
                                            'next_states' =>
                                                array (
                                                    0 => 'Finance_L2_role_FinanceL2_1_1_Approval',
                                                ),
                                        ),
                                    'Finance_L2_role_FinanceL2_1_1_Approval' =>
                                        array (
                                            'current_state' => 'Finance_L2_role_FinanceL2_1_1_Approval',
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
                                                    'min' => 1,
                                                    'max' => 100000,
                                                ),
                                        ),
                                    'Checker_role_RzpChekrRoleId_0_0_Approval' =>
                                        array (
                                            'name' => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                            'group_name' => '1',
                                            'type' => 'checker',
                                            'rules' =>
                                                array (
                                                    'actor_property_key' => 'role',
                                                    'actor_property_value' => 'checker',
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
                                    'Maker_role_RzpMakerRoleId_0_0_Approval' =>
                                        array (
                                            'name' => 'Maker_role_RzpMakerRoleId_0_0_Approval',
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
                                    'And_0_0_Result' =>
                                        array (
                                            'name' => 'And_0_0_Result',
                                            'group_name' => '1',
                                            'type' => 'merge_states',
                                            'rules' =>
                                                array (
                                                    'states' =>
                                                        array (
                                                            0 => 'Checker_role_RzpChekrRoleId_0_0_Approval',
                                                            1 => 'Maker_role_RzpMakerRoleId_0_0_Approval',
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
                                                    'min' => 100000,
                                                    'max' => 20000000000,
                                                ),
                                        ),
                                    'SuperAdmin_role_RzpAdminRoleId_1_0_Approval' =>
                                        array (
                                            'name' => 'SuperAdmin_role_RzpAdminRoleId_1_0_Approval',
                                            'group_name' => '1',
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
                                    'Finance_L1_role_FinanceL1_1_0_Approval' =>
                                        array (
                                            'name' => 'Finance_L1_role_FinanceL1_1_0_Approval',
                                            'group_name' => '1',
                                            'type' => 'checker',
                                            'rules' =>
                                                array (
                                                    'actor_property_key' => 'role',
                                                    'actor_property_value' => 'finance_l1',
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
                                    'Finance_L2_role_FinanceL2_1_1_Approval' =>
                                        array (
                                            'name' => 'Finance_L2_role_FinanceL2_1_1_Approval',
                                            'group_name' => '2',
                                            'type' => 'checker',
                                            'rules' =>
                                                array (
                                                    'actor_property_key' => 'role',
                                                    'actor_property_value' => 'finance_l2',
                                                    'count' => 2,
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
                                    'owner' =>
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
                    'version' => '1',
                    'type' => 'payout-approval',
                    'name' => '10000000000000 - Payout approval workflow',
                    'service' => 'rx_live',
                    'owner_id' => '10000000000000',
                    'owner_type' => 'merchant',
                    'org_id' => '100000razorpay',
                    'enabled' => 'true',
                )
            ]
    ]
];
