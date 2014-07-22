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
                    'id'            =>  1,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  2,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'          =>  1,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'          =>  2,
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
                    'merchant_id'   =>  1,
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
                    'merchant_id'   =>  1,
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
                    'merchant_id'   =>  2,
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
                    'merchant_id'   =>  2,
                    'live'          =>  0,
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::PRICING)->insert(
                array(
                    'id'            => 'c5484d12aafacc2023608c79',
                    'gateway'       => 'hdfc',
                    'payment_method'=> 'credit card',
                    'payment_method_subtype' => 'Diners Club',
                    'plan'          => 'Education',
                    'percent_rate'  => '289',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'e5484d12aafacc2023608c79',
                    'gateway'       => 'hdfc',
                    'payment_method'=> 'credit card',
                    'payment_method_subtype' => 'generic',
                    'plan'          => 'Education',
                    'percent_rate'  => '300',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'c5484d12aafacc2023608c79',
                    'gateway'       => 'hdfc',
                    'payment_method'=> 'debit card',
                    'payment_method_subtype' => 'generic',
                    'plan'          => 'Education',
                    'percent_rate'  => '250',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'b5484d12aafacc2023608c79',
                    'gateway'       => 'icici',
                    'payment_method'=> 'net banking',
                    'payment_method_subtype' => 'generic',
                    'plan'          => 'Education',
                    'percent_rate'  => '0',
                    'fixed_rate'    => '3000',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    )
                );
        });
    }
}
