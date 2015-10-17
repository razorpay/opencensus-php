<?php

use Constants\Mode;
use Constants\Table;
use Models\Merchant\Account;
use Models\Pricing;
use Models\Payment\Processor\Netbanking;
use Models\Terminal;

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
                    'category'      =>  '1234',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'nodal@razorpay.com'
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
                    'category'      =>  '1234',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'nodal@razorpay.com'
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
                    'id'            =>  Account::API_FEE_ACCOUNT,
                    'name'          =>  'Razorpay Fee Account',
                    'email'         =>  'fees@razorpay.com',
                    'category'      =>  '1234',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'fees@razorpay.com'
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::API_FEE_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT,
                    'name'          =>  'Test Account',
                    'email'         =>  'test@razorpay.com',
                    'category'      =>  '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'test@razorpay.com'
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
                    'category'      =>  '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'demo@razorpay.com'
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::DEMO_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            if ($name === Mode::TEST)
            {
                $this->createTestTerminals();
            }

            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   =>  Account::DEMO_ACCOUNT,
                    'banks'         =>  json_encode(Netbanking::getAllBanks()),
                    'paytm'         => '1',
                    'mobikwik'      => '1',
                    'card'          => '1',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );

            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   =>  Account::TEST_ACCOUNT,
                    'banks'         =>  json_encode(Netbanking::getAllBanks()),
                    'paytm'         => '1',
                    'mobikwik'      => '1',
                    'card'          => '1',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );
        });
    }

    protected function createTestTerminals()
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
                'id'                    => '1pnP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'axis_migs',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_axis_migs',
                'gateway_terminal_id'   => 'test_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('test_account_axis_migs_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1xnP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'axis_genius',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_axis_genius',
                'gateway_terminal_id'   => 'test_terminal_axis_genius',
                'gateway_terminal_password' => Crypt::encrypt('test_account_axis_genius_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1ynP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'kotak',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_kotak',
                'gateway_terminal_id'   => 'test_terminal_kotak',
                'gateway_terminal_password' => Crypt::encrypt('test_account_kotak_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1znP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'paytm',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_paytm',
                'gateway_terminal_id'   => 'test_terminal_paytm',
                'gateway_terminal_password' => Crypt::encrypt('test_account_paytm_terminal_pass'),
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

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::ATOM_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'atom',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_atom',
                'gateway_terminal_id'   => 'shared_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'axis_migs',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_axis_migs',
                'gateway_terminal_id'   => 'shared_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_axis_migs_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'axis_genius',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_axis_genius',
                'gateway_terminal_id'   => 'shared_terminal_axis_genius',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_axis_genius_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::KOTAK_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'kotak',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_kotak',
                'gateway_terminal_id'   => 'shared_terminal_kotak',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_kotak_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::PAYTM_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'paytm',
                'card'                  => '0',
                'gateway_merchant_id'   => 'demo_merchant_paytm',
                'gateway_terminal_id'   => 'shared_terminal_paytm',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_paytm_terminal_pass'),
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

        $this->createBilldeskGatewayTerminals();
        $this->createNetbankingHdfcTerminals();
        $this->createMobikwikTerminals();
        $this->createSharpGatewayTerminals();
    }

    protected function createNetbankingHdfcTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '22nP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'netbanking_hdfc',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_hdfc',
                'gateway_terminal_id'   => 'test_terminal_netbanking_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('test_account_netbanking_hdfc_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_HDFC_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'netbanking_hdfc',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_hdfc',
                'gateway_terminal_id'   => 'demo_terminal_netbanking_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_netbanking_hdfc_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );
    }

    protected function createBilldeskGatewayTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '2byKhdVKZ9iJgA',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'billdesk',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_billdesk',
                'gateway_terminal_id'   => 'test_terminal_billdesk',
                'gateway_terminal_password' => Crypt::encrypt('test_account_billdesk_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'billdesk',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_billdesk',
                'gateway_terminal_id'   => 'demo_terminal_billdesk',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_billdesk_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );
    }

    protected function createSharpGatewayTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '2czHdeTG32rFhB',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'sharp',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_sharp',
                'gateway_terminal_id'   => 'test_terminal_sharp',
                'gateway_terminal_password' => Crypt::encrypt('test_account_sharp_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::SHARP_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'sharp',
                'card'                  => '1',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_sharp',
                'gateway_terminal_id'   => 'demo_terminal_sharp',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_sharp_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );
    }

    protected function createMobikwikTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '2dAHgaZd63sHbl',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'mobikwik',
                'card'                  => '0',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_merchant_mobikwik',
                'gateway_terminal_id'   => 'test_terminal_mobikwik',
                'gateway_terminal_password' => Crypt::encrypt('test_account_mobikwik_terminal_pass'),
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::MOBIKWIK_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => 'mobikwik',
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_mobikwik',
                'gateway_terminal_id'       => 'demo_terminal_mobikwik',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_mobikwik_terminal_pass'),
                'created_at'                =>  time(),
                'updated_at'                =>  time(),
                )
            );
    }
}
