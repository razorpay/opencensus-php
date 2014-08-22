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
                    'name'          =>  'Harshil',
                    'email'         =>  'das@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  'f725411687297c5fce0af5c4',
                    'name'          =>  'testname',
                    'email'         =>  'shk@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '14aa47c4a93d6e9b9c7ef51a',
                    'merchant_id'           => '363e4efa820b0c06208ccd99',
                    'gateway'               => 'hdfc',
                    'gateway_merchant_id'   => 'merch123',
                    'gateway_terminal_id'   => '123456',
                    'gateway_terminal_password' => Crypt::encrypt('encryptpass'),
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
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
                    'active'        =>  1,
                    'secret'        =>  Hash::make('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::PRICING)->insert(
                array(
                    'id'            => 'c5484d12aafacc2023608c79',
                    'plan_id'       => '501f6ad55b9a845fe509e09e',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_mode'  => 'card',
                    'payment_mode_type' => 'credit',
                    'payment_network'=> 'DICL',
                    'payment_issuer' => 'HDFC',
                    'percent_rate'  => '289',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'e5484d12aafacc2023608c79',
                    'plan_id'       => '501f6ad55b9a845fe509e09e',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_mode'  => 'card',
                    'payment_mode_type' => 'credit',
                    'percent_rate'  => '300',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'c5484d12aafacc2023608c79',
                    'plan_id'       => '501f6ad55b9a845fe509e09e',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_mode'  => 'card',
                    'payment_mode_type' => 'debit',
                    'percent_rate'  => '250',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'b5484d12aafacc2023608c79',
                    'plan_id'       => '501f6ad55b9a845fe509e09e',
                    'plan_name'     => 'Education',
                    'gateway'       => 'icici',
                    'payment_mode'  => 'nb',
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
