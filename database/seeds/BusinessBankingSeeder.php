<?php

use RZP\Constants\Table;
use Illuminate\Database\Seeder;

class BusinessBankingSeeder extends Seeder
{

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

        // Contacts
        DB::transaction(function() use ($name) {
            DB::table(Table::CONTACT)->insert(
                [
                    [
                        'id'          => 'BXV5GAmaJEcGr1',
                        'name'        => 'Contact 1',
                        'email'       => 'contact1@test.com',
                        'contact'     => '9123456789',
                        'type'        => 'employee',
                        'notes'       => '{}',
                        'merchant_id' => '10000000000000',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                        'deleted_at'  => null,
                    ],
                    [
                        'id'          => 'BXV5GAmaJEcGr2',
                        'name'        => 'Contact 2',
                        'email'       => 'contact2@test.com',
                        'contact'     => '9123456782',
                        'type'        => 'customer',
                        'notes'       => '{}',
                        'merchant_id' => '10000000000000',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                        'deleted_at'  => null,
                    ],
                    [
                        'id'          => 'BXV5GAmaJEcGr3',
                        'name'        => 'Contact 3',
                        'email'       => 'contact3@test.com',
                        'contact'     => '9123456783',
                        'type'        => 'self',
                        'notes'       => '{}',
                        'merchant_id' => '10000000000000',
                        'created_at'  => time(),
                        'updated_at'  => time(),
                        'deleted_at'  => null,
                    ],
                ]
            );
        });
    }
}
