<?php

use Illuminate\Database\Seeder;

use RZP\Constants\Table;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

class PermissionSeeder extends Seeder
{
    protected static $permissions = [];

    protected static $assignablePermissions = [];

    protected static $permissionIds;

    protected static $enableWorkflowPermissions;

    public function __construct()
    {
        self::$permissions = Config::get('heimdall.permissions');

        self::$assignablePermissions = Config::get('heimdall.assignable_permissions');

        self::$enableWorkflowPermissions = Config::get('heimdall.enable_workflow_permissions');
    }

    /**
     * Run the database seeds.
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

        $assignablePermissions = self::$assignablePermissions;

        $enableWorkflowPermissions = self::$enableWorkflowPermissions;

        DB::transaction(function() use ($permissions, $assignablePermissions, $enableWorkflowPermissions)
        {
            $index = 0;

            foreach ($permissions as $category => $details)
            {
                foreach ($details as $permission => $description)
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

                    $data = [
                        'id'          => $id,
                        'name'        => $permission,
                        'description' => $description,
                        'category'    => $category,
                        'created_at'  => time(),
                        'updated_at'  => time(),
                    ];

                    // For trimmed down ones (like HDFC)
                    if (isset($enableWorkflowPermissions[$category]) and
                        isset($enableWorkflowPermissions[$category][$permission]))
                    {
                        $data['enable_workflow'] = 1;
                    }

                    DB::table(Table::PERMISSION)->insert($data);

                    DB::table(Table::PERMISSION_MAP)->insert([
                        [
                            'permission_id'     => $id,
                            'entity_id'         => '6dLbNSpv5XbC5E',
                            'entity_type'       => 'role',
                        ]
                    ]);

                    // Razorpay Org will have all permissions
                    DB::table(Table::PERMISSION_MAP)->insert([
                        [
                            'permission_id' => $id,
                            'entity_id'     => '100000razorpay',
                            'entity_type'   => 'org',
                        ]
                    ]);

                    // For trimmed down ones (like HDFC)
                    if (isset($assignablePermissions[$category]) and
                        isset($assignablePermissions[$category][$permission]))
                    {
                        DB::table(Table::PERMISSION)
                            ->where('id', $id)
                            ->update(['assignable' => 1]);

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
