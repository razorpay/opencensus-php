<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Table;
use RZP\Models\Admin\Permission;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
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

        $this->seed();
    }

    private function seed()
    {
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            $permissions = (new Permission\Repository)->retrieveIdsByNames(
                ['edit_admin'])->toArray();

            $permissionId = $permissions[0]['id'];

            DB::table(Table::WORKFLOW)->insert(
                [
                    'id'         =>  '7bdlc7lgfoqx3h',
                    'name'       =>  'Some name one',
                    'org_id'     =>  '100000razorpay',
                    'created_at' =>  time(),
                    'updated_at' =>  time(),
                    'deleted_at' =>  null,
                ]);

            DB::table(Table::WORKFLOW_STEP)->insert(
                [
                    [
                        'id'             => '7bdpejde2CdyPG',
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'workflow_id'    => '7bdlc7lgfoqx3h',
                        'op_type'        => 'or',
                        'role_id'        => '7bdvjyhVRgcfCe',
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => '7bdsihKWhkk9f7',
                        'level'          => 1,
                        'reviewer_count' => 1,
                        'op_type'        => 'or',
                        'workflow_id'    => '7bdlc7lgfoqx3h',
                        'role_id'        => '7bdvYPtRHEw3zB',
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                    [
                        'id'             => '7bdt2wDyxcEeXH',
                        'level'          => 2,
                        'reviewer_count' => 1,
                        'op_type'        => 'and',
                        'workflow_id'    => '7bdlc7lgfoqx3h',
                        'role_id'        => '7bdyCqxHR23Y9u',
                        'created_at'     => time(),
                        'updated_at'     => time(),
                    ],
                ]);

            DB::table('workflow_permissions')->insert(
                [
                    'workflow_id'   => '7bdlc7lgfoqx3h',
                    'permission_id' => $permissionId,
                ]);

            DB::table(Table::ADMIN)->insert([
                [
                    'id'                  => '7beXdd9yaGRW2Y',
                    'email'               => 'abc@razorpay.com',
                    'name'                => 'Checker One',
                    'username'            => 'abc',
                    'org_id'              => self::RAZORPAY_ORG_ID,
                    'last_login_at'       => null,
                    'created_at'          => time(),
                    'updated_at'          => time(),
                    'allow_all_merchants' => true,
                ],

                [
                    'id'                  => '7bebOFeztY037p',
                    'email'               => 'abctwo@razorpay.com',
                    'name'                => 'Checker Two',
                    'username'            => 'abc',
                    'org_id'              => self::RAZORPAY_ORG_ID,
                    'last_login_at'       => null,
                    'created_at'          => time(),
                    'updated_at'          => time(),
                    'allow_all_merchants' => true,
                ],

                [
                    'id'                  => '7bebhTeCKmV9KJ',
                    'email'               => 'abcthree@razorpay.com',
                    'name'                => 'Checker Three',
                    'username'            => 'abc',
                    'org_id'              => self::RAZORPAY_ORG_ID,
                    'last_login_at'       => null,
                    'created_at'          => time(),
                    'updated_at'          => time(),
                    'allow_all_merchants' => true,
                ],
                [
                    'id'                  => '7behsrdMNUpcRa',
                    'email'               => 'makerone@razorpay.com',
                    'name'                => 'Maker One',
                    'username'            => 'abc',
                    'org_id'              => self::RAZORPAY_ORG_ID,
                    'last_login_at'       => null,
                    'created_at'          => time(),
                    'updated_at'          => time(),
                    'allow_all_merchants' => true,
                ],
            ]);

            DB::table('permission_map')->insert(
                // maker role
                [
                    'entity_id'     => '7bdxOwCk3Eyu0R',
                    'entity_type'   => 'role',
                    'permission_id' => $permissionId,
                ]);

            DB::table('role_map')->insert([

                // checker three
                [
                    'role_id'     => '7bdyCqxHR23Y9u',
                    'entity_type' => 'admin',
                    'entity_id'   => '7bebhTeCKmV9KJ',
                ],

                // checker two
                [
                    'role_id'     => '7bdvYPtRHEw3zB',
                    'entity_type' => 'admin',
                    'entity_id'   => '7bebOFeztY037p',
                ],

                // checker one
                [
                    'role_id'     => '7bdvjyhVRgcfCe',
                    'entity_type' => 'admin',
                    'entity_id'   => '7beXdd9yaGRW2Y',
                ],

                // maker one
                [
                    'role_id'     => '7bdxOwCk3Eyu0R',
                    'entity_type' => 'admin',
                    'entity_id'   => '7behsrdMNUpcRa',
                ],
            ]);

            DB::table(Table::ADMIN_TOKEN)->insert([
                // checker one
                [
                    'id'                  => '7beuOGGSi8tkR1',
                    'admin_id'            => '7beXdd9yaGRW2Y',
                    'token'               => '123456',
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ],

                // checker two
                [
                    'id'                  => '7beulP0oaOnakJ',
                    'admin_id'            => '7bebOFeztY037p',
                    'token'               => '123457',
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ],

                // checker 3
                [
                    'id'                  => '7beuv6yJT6SYp8',
                    'admin_id'            => '7bebhTeCKmV9KJ',
                    'token'               => '123458',
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ],

                // maker one
                [
                    'id'                  => '7bev5KMHVAfGgB',
                    'admin_id'            => '7behsrdMNUpcRa',
                    'token'               => '123459',
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ],

            ]);


        });
    }
}
