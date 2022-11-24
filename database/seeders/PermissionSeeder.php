<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use RZP\Constants\Table;

class PermissionSeeder extends Seeder
{
    protected static $permissions = [];

    protected static $permissionIds;

    public function __construct()
    {
        self::$permissions = Config::get('heimdall.permissions');
    }

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        DB::table(Table::PERMISSION)->delete();

        $this->seed();
    }

    private function seed()
    {
        $permissions = self::$permissions;

        DB::transaction(function() use ($permissions)
        {
            $index = 0;

            foreach ($permissions as $category => $details)
            {
                foreach ($details as $permission => $permissionValue)
                {
                    if (isset(self::$permissionIds[$index]) === true)
                    {
                        $id = self::$permissionIds[$index];
                    }
                    else
                    {
                        $id = str_random(14);

                        self::$permissionIds[] = $id;
                    }

                    $index++;

                    $desc = isset($permissionValue['description']) ? $permissionValue['description'] : '';
                    $assignable = isset($permissionValue['assignable']) ? $permissionValue['assignable'] : false;
                    $workflow = isset($permissionValue['workflow']) ? $permissionValue['workflow'] : false;

                    DB::table(Table::PERMISSION)->insert([
                        'id'          => $id,
                        'name'        => $permission,
                        'description' => $desc,
                        'category'    => $category,
                        'created_at'  => time(),
                        'updated_at'  => time(),
                        'assignable'  => $assignable
                    ]);

                    DB::table(Table::PERMISSION_MAP)->insert([
                        'permission_id'     => $id,
                        'entity_id'         => '6dLbNSpv5XbC5E',
                        'entity_type'       => 'role',
                    ]);

                    $data = [
                        'permission_id' => $id,
                        'entity_id'     => '100000razorpay',
                        'entity_type'   => 'org',
                        'enable_workflow' => $workflow
                    ];

                    // Razorpay Org will have all permissions
                    DB::table(Table::PERMISSION_MAP)->insert($data);

                    // For trimmed down ones (like HDFC)
                    if (isset($permissionValue['assignable']) and
                        $permissionValue['assignable'])
                    {
                        DB::table(Table::PERMISSION_MAP)->insert([
                            [
                                'permission_id'     => $id,
                                'entity_id'         => '6dLbNSpv5XbC5F',
                                'entity_type'       => 'role',
                            ]
                        ]);

                        // Trimmed down permissions for HDFC Bank
                        DB::table(Table::PERMISSION_MAP)->insert([
                            [
                                'permission_id' => $id,
                                'entity_id'     => '6dLbNSpv5XbCOG',
                                'entity_type'   => 'org',
                            ]
                        ]);
                    }
                }
            }
            // end of transaction
        });
    }
}
