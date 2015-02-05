<?php

use Constants\Table;
use Models\Merchant\Account;
use Models\Pricing;
use Models\Payment\Processor\NetBanking;

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
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            $pricingSeedData = Pricing\DefaultPlan::getPricingSeedData();

            DB::table(Table::PRICING)->insert(
                $pricingSeedData);

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
                    'id'            =>  Account::ATOM_ACCOUNT,
                    'name'          =>  'Razorpay Atom Account',
                    'email'         =>  'atom@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::ATOM_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT,
                    'name'          =>  'Test Account',
                    'email'         =>  'test@razorpay.com',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
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
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
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

            if ($name === 'test')
            {
                $this->createTerminals();
            }

            DB::table(Table::MERCHANT_BANKS)->insert(
                array(
                    'merchant_id'   =>  Account::DEMO_ACCOUNT,
                    'banks'         =>  json_encode(NetBanking::getAllBanks()),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );

            DB::table(Table::MERCHANT_BANKS)->insert(
                array(
                    'merchant_id'   =>  Account::TEST_ACCOUNT,
                    'banks'         =>  json_encode(NetBanking::getAllBanks()),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );
        });
    }

    protected function createTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25f6uN5S1Z5a',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'hdfc',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_hdfc',
                'gateway_terminal_id'   => 'test_terminal_hdfc',
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
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_atom',
                'gateway_terminal_id'   => 'test_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('test_account_atom_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1VwJebUIU7hIhU',
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'hdfc',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_hdfc',
                'gateway_terminal_id'   => 'demo_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1XwJrbxrfB0i8G',
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'atom',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_atom',
                'gateway_terminal_id'   => 'demo_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
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
    }
}
