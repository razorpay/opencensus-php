<?php

use Constants\Table;

class DatabaseSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $this->seed();

        $this->call('IinsTableSeeder');
    }

    private function seed()
    {
        DB::transaction(function()
        {
            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  '363e4efa820b0c06208ccd99',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  'f725411687297c5fce0af5c4',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  '363e4efa820b0c06208ccd99',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  'f725411687297c5fce0af5c4',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::CARD)->insert(
                array(
                    'id'            =>  '174bdd3e456c8f6f174bdd3e',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '99',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'last4'         =>  '7890',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::CARD)->insert(
                array(
                    'id'            =>  '274bdd3e456c8f6f174bdd3f',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '12',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'last4'         =>  '7891',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c1',
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'live'          =>  1,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c2',
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'live'          =>  0,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c3',
                    'merchant_id'   =>  'f725411687297c5fce0af5c4',
                    'live'          =>  1,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c4',
                    'merchant_id'   =>  'f725411687297c5fce0af5c4',
                    'live'          =>  0,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );
        });
    }

}
