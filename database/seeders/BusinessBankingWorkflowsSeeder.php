<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Table;
use RZP\Models\Admin\Permission;

class BusinessBankingWorkflowsSeeder extends Seeder
{
    const RAZORPAY_ORG_ID = '100000razorpay';

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Eloquent::unguard();

        DB::transaction(function()
        {
            $this->seedWorkflowTables();

            $this->seedPayoutWorkflowsFeature();
        });
    }

    private function seedWorkflowTables()
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

        $workflow1Id = str_random(14);
        $workflow2Id = str_random(14);
        $workflow3Id = str_random(14);

        // Seed workflows
        DB::table(Table::WORKFLOW)->insert(
            [
                [
                    'id'          => $workflow1Id,
                    'name'        => 'Three Step: Below 10000',
                    'org_id'      => '100000razorpay',
                    'merchant_id' => '10000000000000',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => $workflow2Id,
                    'name'        => 'Three Step: 10001 to 100000',
                    'org_id'      => '100000razorpay',
                    'merchant_id' => '10000000000000',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => $workflow3Id,
                    'name'        => 'Three Step: >= 100001',
                    'org_id'      => '100000razorpay',
                    'merchant_id' => '10000000000000',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
            ]);

        // Seed workflow steps
        DB::table(Table::WORKFLOW_STEP)->insert(
            [
                //Steps for first workflow - Three Step: Below 10000
                [
                    'id'             => str_random(14),
                    'level'          => 1,
                    'reviewer_count' => 1,
                    'workflow_id'    => $workflow1Id,
                    'op_type'        => 'or',
                    'role_id'        => $financeL2RoleId,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ],

                // Steps for second workflow - Three Step: 10001 - 100000
                [
                    'id'             => str_random(14),
                    'level'          => 1,
                    'reviewer_count' => 1,
                    'workflow_id'    => $workflow2Id,
                    'op_type'        => 'and',
                    'role_id'        => $financeL2RoleId,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ],
                [
                    'id'             => str_random(14),
                    'level'          => 1,
                    'reviewer_count' => 1,
                    'workflow_id'    => $workflow2Id,
                    'op_type'        => 'and',
                    'role_id'        => $financeL3RoleId,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ],

                // Steps for third workflow - Three Step: >= 100001
                [
                    'id'             => str_random(14),
                    'level'          => 1,
                    'reviewer_count' => 1,
                    'workflow_id'    => $workflow3Id,
                    'op_type'        => 'or',
                    'role_id'        => $financeL3RoleId,
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

        DB::table(Table::WORKFLOW_PAYOUT_AMOUNT_RULES)->insert(
            [
                [
                    'merchant_id' => '10000000000000',
                    'min_amount'  => 0,
                    'max_amount'  => 1000000,
                    'workflow_id' => $workflow1Id,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
                [
                    'merchant_id' => '10000000000000',
                    'min_amount'  => 1000100,
                    'max_amount'  => 10000000,
                    'workflow_id' => $workflow2Id,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
                [
                    'merchant_id' => '10000000000000',
                    'min_amount'  => 10000100,
                    'max_amount'  => null,
                    'workflow_id' => $workflow3Id,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
            ]);
    }

    private function seedPayoutWorkflowsFeature()
    {
        DB::table(Table::FEATURE)->insert(
            [
                [
                    'id'          => 'feature_x5x5x5',
                    'name'        => 'payout_workflows',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ]
            ]);
    }
}
