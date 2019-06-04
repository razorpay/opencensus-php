<?php
/**
 * Created by PhpStorm.
 * User: venkateswarluyerramalli
 * Date: 2019-05-17
 * Time: 15:55
 */

use Illuminate\Database\Seeder;

use RZP\Constants\Table;

class MerchantMakerCheckerSeeder extends Seeder
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
            //Seed the roles
            $l1id = str_random(14);
            $l2id = str_random(14);
            $l3id = str_random(14);

            DB::table(Table::ROLE)->insert([
                                               // RZP
                                               [
                                                   'id'          => $l1id,
                                                   'name'        => 'Finance L1',
                                                   'description' => 'Finanace L1',
                                                   'org_id'      => self::RAZORPAY_ORG_ID,
                                                   'created_at'  => time(),
                                                   'updated_at'  => time(),
                                               ],
                                               // HDFC
                                               [
                                                   'id'          => $l2id,
                                                   'name'        => 'Finance L2',
                                                   'description' => 'Finanace L2',
                                                   'org_id'      => self::RAZORPAY_ORG_ID,
                                                   'created_at'  => time(),
                                                   'updated_at'  => time(),
                                               ],
                                               [
                                                   'id'          => $l3id,
                                                   'name'        => 'Finance L3',
                                                   'description' => 'Finanace L3',
                                                   'org_id'      => self::RAZORPAY_ORG_ID,
                                                   'created_at'  => time(),
                                                   'updated_at'  => time(),
                                               ],

                                           ]);

            $createPayoutPerm = (new Permission\Repository)->retrieveIdsByNames(
                ['create_payout'])->toArray()[0]['id'];
            $editPayoutPerm   = (new Permission\Repository)->retrieveIdsByNames(
                ['edit_payout'])->toArray()[0]['id'];
            $viewPayoutPerm   = (new Permission\Repository)->retrieveIdsByNames(
                ['view_payout'])->toArray()[0]['id'];
            $bulkApprRejPerm  = (new Permission\Repository)->retrieveIdsByNames(
                ['bulk_approve_reject_payout'])->toArray()[0]['id'];

            DB::table(Table::PERMISSION_MAP)->insert(
                [
                    // Create Payout Permission mapping to L1, L2 and L3 roles
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $l1id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $l2id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $createPayoutPerm,
                        'entity_id'     => $l3id,
                        'entity_type'   => 'role',
                    ],

                    // Edit Payout Permission mapping to L1, L2 and L3 roles
                    [
                        'permission_id' => $editPayoutPerm,
                        'entity_id'     => $l1id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $editPayoutPerm,
                        'entity_id'     => $l2id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $editPayoutPerm,
                        'entity_id'     => $l3id,
                        'entity_type'   => 'role',
                    ],

                    // View Payout Permission mapping to L1, L2 and L3 roles
                    [
                        'permission_id' => $viewPayoutPerm,
                        'entity_id'     => $l1id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $viewPayoutPerm,
                        'entity_id'     => $l2id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $viewPayoutPerm,
                        'entity_id'     => $l3id,
                        'entity_type'   => 'role',
                    ],

                    // Bulk Approve/Reject Payout Permission mapping to L1, L2 and L3 roles
                    [
                        'permission_id' => $bulkApprRejPerm,
                        'entity_id'     => $l1id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $bulkApprRejPerm,
                        'entity_id'     => $l2id,
                        'entity_type'   => 'role',
                    ],
                    [
                        'permission_id' => $bulkApprRejPerm,
                        'entity_id'     => $l3id,
                        'entity_type'   => 'role',
                    ],
                ]);

            $wf1id = str_random(14); //SINGLE_STEP_SINGLE_CHECKER
            $wf2id = str_random(14); //SINGLE_STEP_TWO_CHECKERS
            $wf3id = str_random(14); //TWO_STEPS_TWO_CHECKERS

            //Initialize workflows

            DB::table(Table::WORKFLOW)->insert(
                [
                    [
                        'id'         => $wf1id,
                        'name'       => 'Single Step Single Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                    [
                        'id'         => $wf2id,
                        'name'       => 'Single Step Two Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                    [
                        'id'         => $wf3id,
                        'name'       => 'Two Step Two Checker',
                        'org_id'     => '100000razorpay',
                        'created_at' => time(),
                        'updated_at' => time(),
                        'deleted_at' => null,
                    ],
                ]);

            DB::table(Table::WORKFLOW_STEP)->insert(
                [
                    //Steps for First workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $wf1id,
                        'op_type'        => 'or',
                        'role_id'        => $l1id,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],

                    //Steps for Second workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $wf2id,
                        'op_type'        => 'or',
                        'role_id'        => $l1id,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $wf2id,
                        'op_type'        => 'or',
                        'role_id'        => $l2id,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],

                    //Steps for Third workflow
                    [
                        'id'             => str_random(14),
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => $wf3id,
                        'op_type'        => 'or',
                        'role_id'        => $l1id,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => str_random(14),
                        'level'          => 2,
                        'reviewer_count' => 1,
                        'workflow_id'    => $wf3id,
                        'op_type'        => 'or',
                        'role_id'        => $l2id,
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                ]);

            DB::table(Table::WORKFLOW_PERMISSION)->insert(
                [
                    //Map create, edit, approve/reject to workflow 1
                    [
                        'workflow_id'   => $wf1id,
                        'permission_id' => $createPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf1id,
                        'permission_id' => $editPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf1id,
                        'permission_id' => $bulkApprRejPerm,
                    ],

                    //Map create, edit, approve/reject to workflow 2
                    [
                        'workflow_id'   => $wf2id,
                        'permission_id' => $createPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf2id,
                        'permission_id' => $editPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf2id,
                        'permission_id' => $bulkApprRejPerm,
                    ],

                    //Map create, edit, approve/reject to workflow 3
                    [
                        'workflow_id'   => $wf3id,
                        'permission_id' => $createPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf3id,
                        'permission_id' => $editPayoutPerm,
                    ],
                    [
                        'workflow_id'   => $wf3id,
                        'permission_id' => $bulkApprRejPerm,
                    ],
                ]);
        });

    }
}
