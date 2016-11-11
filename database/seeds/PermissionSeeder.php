<?php

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        DB::table('permissions')->delete();

        $this->seed();
    }

    private function seed()
    {
        $name = DB::connection()->getName();
        $permissions = ['add_permission','org_create','org_get_multiple','org_get','org_edit','role_get_multiple','role_create','group_get_multiple','group_create','group_get','group_admins_create','admin_get_multiple','admin_edit','admin_delete','admin_create','permission_get_multiple'];

        DB::transaction(function() use ($permissions)
        {
            foreach ($permissions as $value) {
                DB::table('permissions')->insert(
                    [
                        'id'          => str_random(14),
                        'name'        => $value,
                        'description' => 'Some description',
                        'created_at'  => time(),
                        'updated_at'  => time()
                    ]);
            }
            
        });
    }
}
