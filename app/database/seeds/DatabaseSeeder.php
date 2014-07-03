<?php

use Models\Manager\UniqueId;

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
            DB::table('merchants')->insert(
                array(
                    'id'            =>  1,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table('merchants')->insert(
                array(
                    'id'            =>  2,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table('cards')->insert(
                array(
                    'id'            =>  '174bdd3e456c8f6f174bdd3e',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '99',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'last4'         =>  '7890',
                    )
                );

            DB::table('cards')->insert(
                array(
                    'id'            =>  '274bdd3e456c8f6f174bdd3f',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '12',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'last4'         =>  '7891',
                    )
                );

            DB::table('tokens')->insert(
                array(
                    'id'            =>  '174bdd3e456c8f6f174bdd3e',
                    'card_id'       =>  '274bdd3e456c8f6f174bdd3f',
                    'merchant_id'   =>  1,
                    )
                );

            DB::table('keys')->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c1',
                    'merchant_id'   =>  1,
                    'live'          =>  1,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table('keys')->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c2',
                    'merchant_id'   =>  1,
                    'live'          =>  0,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table('keys')->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c3',
                    'merchant_id'   =>  2,
                    'live'          =>  1,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table('keys')->insert(
                array(
                    'id'            =>  'd9c6bf091a1a64cb5678d8c4',
                    'merchant_id'   =>  2,
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
