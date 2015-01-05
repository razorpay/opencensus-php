<?php

use Constants\Table;
use Models\Merchant\Account;

class DatabaseSeeder extends Seeder
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

        $this->call('IinsTableSeeder');
    }

    private function seed()
    {
        DB::transaction(function()
        {
            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::NODAL_ACCOUNT,
                    'name'          =>  'Razorpay Nodal Account',
                    'email'         =>  'nodal@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::NODAL_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT,
                    'name'          =>  'Test Account',
                    'email'         =>  'test@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::DEMO_ACCOUNT,
                    'name'          =>  'Demo Account',
                    'email'         =>  'demo@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::DEMO_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1n25f6uN5S1Z5a',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'gateway'               => 'hdfc',
                    'gateway_merchant_id'   => 'test_account_hdfc',
                    'gateway_terminal_id'   => 'test_account_terminal',
                    'gateway_terminal_password' => Crypt::encrypt('test_account_hdfc_terminal_pass'),
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1BjhC5CJAqNF7R',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'gateway'               => 'atom',
                    'gateway_merchant_id'   => 'test_account_atom',
                    'gateway_terminal_id'   => '',
                    'gateway_terminal_password' => Crypt::encrypt('test_account_atom_terminal_pass'),
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT_KEY_ID,
                    'merchant_id'   =>  Account::TEST_ACCOUNT,
                    'secret'        =>  Crypt::encrypt('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  Account::DEMO_ACCOUNT_KEY_ID,
                    'merchant_id'   =>  Account::DEMO_ACCOUNT,
                    'secret'        =>  Crypt::encrypt('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::PRICING)->insert(
                array(
                    'id'            => '1GuENK6Hl2BWGx',
                    'plan_id'       => '1AXludj60w4pSp',
                    'plan_name'     => 'Full Price',
                    'payment_method'  => 'card',
                    'percent_rate'  => '290',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1GuENK6Xk6a8I0',
                    'plan_id'       => '1AXludj60w4pSp',
                    'plan_name'     => 'Full Price',
                    'payment_method'  => 'netbanking',
                    'percent_rate'  => '290',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1L8dUj9MzP3Bj3',
                    'plan_id'       => '1In3Yh5Mluj605',
                    'plan_name'     => 'Promotional Price',
                    'payment_method'  => 'card',
                    'percent_rate'  => '200',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1Nsi8IbQ3pWP7T',
                    'plan_id'       => '1In3Yh5Mluj605',
                    'plan_name'     => 'Promotional Price',
                    'payment_method'  => 'netbanking',
                    'percent_rate'  => '200',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    )
                );
        });
    }
}
