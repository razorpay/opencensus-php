<?php

use Illuminate\Database\Seeder;

use RZP\Constants\Table;
use RZP\Models\Admin\Permission;

class BusinessBankingWorkflowsSeeder extends Seeder
{
    const RAZORPAY_ORG_ID = '100000razorpay';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        $this->seed();
    }

    private function seed()
    {
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            // Role IDs
            $financeL1RoleId = str_random(14);
            $financeL2RoleId = str_random(14);
            $financeL3RoleId = str_random(14);

            DB::table(Table::ROLE)->insert(
                [
                    [
                        'id'          => $financeL1RoleId,
                        'name'        => 'Finance L1',
                        'description' => 'Finance L1',
                        'org_id'      => self::RAZORPAY_ORG_ID,
                        'product'     => 'banking',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                    ],
                    [
                        'id'          => $financeL2RoleId,
                        'name'        => 'Finance L2',
                        'description' => 'Finance L2',
                        'org_id'      => self::RAZORPAY_ORG_ID,
                        'product'     => 'banking',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                    ],
                    [
                        'id'          => $financeL3RoleId,
                        'name'        => 'Finance L3',
                        'description' => 'Finance L3',
                        'org_id'      => self::RAZORPAY_ORG_ID,
                        'product'     => 'banking',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                    ],
                ]);

            $createPayoutPerm = (new Permission\Repository)->retrieveIdsByNames(['create_payout'])->pluck('id')[0];

            DB::table(Table::PERMISSION_MAP)->insert(
                [
                    // Create Payout Permission mapping to L1, L2 and L3 roles
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $financeL1RoleId,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $financeL2RoleId,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $financeL3RoleId,
                        'entity_type'   => 'role',
                    ],
                ]);

            $workflow1Id = str_random(14); // SINGLE_STEP_SINGLE_CHECKER
            $workflow2Id = str_random(14); // SINGLE_STEP_TWO_CHECKERS
            $workflow3Id = str_random(14); // TWO_STEPS_TWO_CHECKERS

            // Seed workflows
            DB::table(Table::WORKFLOW)->insert(
                [
                    [
                        'id'         => $workflow1Id,
                        'name'       => 'Single Step Single Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                    [
                        'id'         => $workflow2Id,
                        'name'       => 'Single Step Two Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                    [
                        'id'         => $workflow3Id,
                        'name'       => 'Two Step Two Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                ]);

            // Seed workflow steps
            DB::table(Table::WORKFLOW_STEP)->insert(
                [
                    //Steps for First workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $workflow1Id,
                        'op_type'        => 'or',
                        'role_id'        => $financeL1RoleId,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],

                    // Steps for Second workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $workflow2Id,
                        'op_type'        => 'or',
                        'role_id'        => $financeL1RoleId,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $workflow2Id,
                        'op_type'        => 'or',
                        'role_id'        => $financeL2RoleId,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],

                    // Steps for Third workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $workflow3Id,
                        'op_type'        => 'or',
                        'role_id'        => $financeL1RoleId,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => str_random(14),
                        'level'          => 2,
                        'reviewer_count' => 1,
                        'workflow_id'    => $workflow3Id,
                        'op_type'        => 'or',
                        'role_id'        => $financeL2RoleId,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                ]);

            DB::table(Table::WORKFLOW_PERMISSION)->insert(
                [
                    [
                        'workflow_id'   => $workflow1Id,
                        'permission_id' => $createPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $workflow2Id,
                        'permission_id' => $createPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $workflow3Id,
                        'permission_id' => $createPayoutPerm,
                    ],
                ]);

            // Define payout amount rules for workflow 1
            DB::table(Table::WORKFLOW_PAYOUT_AMOUNT_RULES)->insert(
                [
                    [
                        'merchant_id'   => '10000000000000',
                        'condition'     => 'BETWEEN_X_AND_Y',
                        'x_amount'      => 0,
                        'y_amount'      => 100000,
                        'workflow_id'   => $workflow1Id,
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'condition'     => 'BETWEEN_X_AND_Y',
                        'x_amount'      => 100100,
                        'y_amount'      => 1000000,
                        'workflow_id'   => $workflow1Id,
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'condition'     => 'GREATER_THAN_X',
                        'x_amount'      => 1000100,
                        'y_amount'      => null,
                        'workflow_id'   => $workflow1Id,
                        'created_at'    => time(),
                        'updated_at'    => time(),
                    ],
                ]);
        });
    }
}
