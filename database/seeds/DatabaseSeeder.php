<?php

use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Merchant\Account;
use RZP\Models\Pricing;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

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

            DB::table(Table::MERCHANT_SCHEDULE)->insert(
                array(
                    'id'          => '1',
                    'merchant_id' => '10000000000000',
                    'schedule_id' => 'schd_basic_t3',
                    'last_run'    => null,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    )
                );

            DB::table(Table::SCHEDULE)->insert(
                array(
                    'id'         => 'schd_2_hourly',
                    'name'       => 'Every 2 hours',
                    'owner_id'   => '100000Razorpay',
                    'type'       => 'settlement',
                    'period'     => 'hourly',
                    'interval'   => 2,
                    'anchor'     => null,
                    'delay'      => 3600,
                    'created_at' => time(),
                    'updated_at' => time(),
                    )
                );

            DB::table(Table::SCHEDULE)->insert(
                array(
                    'id'         => 'schd_basic_t3',
                    'name'       => 'Basic T3',
                    'owner_id'   => '100000Razorpay',
                    'type'       => 'settlement',
                    'period'     => 'daily',
                    'interval'   => 1,
                    'anchor'     => null,
                    'delay'      => 259200,
                    'created_at' => time(),
                    'updated_at' => time(),
                    )
                );

            DB::table(Table::SCHEDULE)->insert(
                array(
                    'id'         => 'schd_tuesdays',
                    'name'       => 'Every Tuesday',
                    'owner_id'   => '100000Razorpay',
                    'type'       => 'settlement',
                    'period'     => 'weekly',
                    'interval'   => 1,
                    'anchor'     => 2,
                    'delay'      => 86400,
                    'created_at' => time(),
                    'updated_at' => time(),
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::NODAL_ACCOUNT,
                    'name'          =>  'Razorpay Nodal Account',
                    'email'         =>  'nodal@razorpay.com',
                    'category'      =>  '1234',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'nodal@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
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
                    'transaction_report_email'=>'nodal@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
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
                    'transaction_report_email'=>'fees@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
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
                    'transaction_report_email'=>'test@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::TEST_ACCOUNT,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'balance'       =>  100000,
                    'credits'       =>  50000,
                    'on_hold'       =>  10000,
                )
            );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  Account::SHARED_ACCOUNT,
                    'name'          =>  'Shared Account',
                    'email'         =>  'shared@razorpay.com',
                    'category'      =>  '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'transaction_report_email'=>'shared@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  Account::SHARED_ACCOUNT,
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
                    'transaction_report_email'=>'demo@razorpay.com',
                    'settlement_schedule' => 3,
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
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

                $this->createLocalCustomer();
            }

            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   =>  Account::DEMO_ACCOUNT,
                    'banks'         =>  json_encode(Netbanking::getAllBanks()),
                    'paytm'         => '1',
                    'olamoney'      => '1',
                    'freecharge'    => '1',
                    'mobikwik'      => '1',
                    'payzapp'       => '1',
                    'payumoney'     => '1',
                    'airtelmoney'   => '1',
                    'card'          => '1',
                    'upi'           => '1',
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
                    'olamoney'      => '1',
                    'freecharge'    => '1',
                    'payzapp'       => '1',
                    'payumoney'     => '1',
                    'airtelmoney'   => '1',
                    'card'          => '1',
                    'emi'           => '1',
                    'upi'           => '1',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );

            DB::table(Table::EMI_PLAN)->insert(
                array(
                    'id'            => 'abcdefghijklmn',
                    'bank'          => 'KKBK',
                    'duration'      => 9,
                    'rate'          => 1400,
                    'min_amount'    => 300000,
                    'methods'       => 'card',
                    'created_at'    => time(),
                    'updated_at'    => time(),
                )
            );
        });
    }

    protected function createLocalCustomer()
    {
        DB::table(Table::CUSTOMER)->insert(
            array(
                array(
                    'id'                    => '64UtLHKtfc7Nn1',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'name'                  => 'User Name',
                    'contact'               => '+919988776655',
                    'email'                 => 'test@razorpay.com',
                    'notes'                 => '{}',
                    'active'                => true,
                    'created_at'            => time(),
                    'updated_at'            => time(),
                ),
                array(
                    'id'                    => '64UtWc2MICesZc',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'name'                  => 'Username',
                    'contact'               => '+919988776644',
                    'email'                 => 'test2@razorpay.com',
                    'notes'                 => '{}',
                    'active'                => true,
                    'created_at'            => time(),
                    'updated_at'            => time(),
                ),
                array(
                    'id'                    => '64UtcV0BN2RVsW',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'name'                  => 'User name',
                    'contact'               => '+919988776633',
                    'email'                 => 'test3@razorpay.com',
                    'notes'                 => '{}',
                    'active'                => true,
                    'created_at'            => time(),
                    'updated_at'            => time(),
                ),
            )
        );
    }

    protected function createTestTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25f6uN5S1Z5a',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_hdfc',
                'gateway_terminal_id'   => 'test_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('test_account_hdfc_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1BjhC5CJAqNF7R',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::ATOM,
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_atom',
                'gateway_terminal_id'   => 'test_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('test_account_atom_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1pnP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AXIS_MIGS,
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_axis_migs',
                'gateway_terminal_id'   => 'test_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('test_account_axis_migs_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1xnP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AXIS_GENIUS,
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_axis_genius',
                'gateway_terminal_id'   => 'test_terminal_axis_genius',
                'gateway_terminal_password' => Crypt::encrypt('test_account_axis_genius_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1znP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::PAYTM,
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_paytm',
                'gateway_terminal_id'   => 'test_terminal_paytm',
                'gateway_terminal_password' => Crypt::encrypt('test_account_paytm_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1VwJebUIU7hIhU',
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_hdfc',
                'gateway_terminal_id'   => 'demo_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1XwJrbxrfB0i8G',
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::ATOM,
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_atom',
                'gateway_terminal_id'   => 'demo_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::ATOM_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::ATOM,
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_atom',
                'gateway_terminal_id'   => 'shared_terminal_atom',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::AXIS_MIGS,
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_axis_migs',
                'gateway_terminal_id'   => 'shared_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_axis_migs_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::FIRST_DATA_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::FIRST_DATA,
                'card'                      => '1',
                'gateway_merchant_id'       => 'demo_merchant_first_data',
                'gateway_terminal_id'       => 'shared_terminal_first_data',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_first_data_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::AXIS_GENIUS,
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_axis_genius',
                'gateway_terminal_id'   => 'shared_terminal_axis_genius',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_axis_genius_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::PAYTM_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::PAYTM,
                'card'                  => '0',
                'gateway_merchant_id'   => 'demo_merchant_paytm',
                'gateway_terminal_id'   => 'shared_terminal_paytm',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_paytm_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => 'djfksjfksjfdkj',
                'merchant_id'           => Account::SHARED_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'emi'                   => '1',
                'shared'                => '1',
                'emi_duration'          => 9,
                'gateway_merchant_id'   => 'test_merchant_emi',
                'gateway_terminal_id'   => 'shared_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                'recurring'             => 0,
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

        $this->createAmexTerminals();
        $this->createBilldeskGatewayTerminals();
        $this->createNetbankingHdfcTerminals();
        $this->createMobikwikTerminals();
        $this->createPayzappTerminals();
        $this->createPayumoneyTerminals();
        $this->createSharpGatewayTerminals();
        $this->createNetbankingKotakTerminals();
        $this->createOlamoneyTerminals();
        $this->createUpiTerminals();
        $this->createAirtelmoneyTerminals();
        $this->createFreechargeTerminals();
    }

    protected function createNetbankingHdfcTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '22nP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_HDFC,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_hdfc',
                'gateway_terminal_id'   => 'test_terminal_netbanking_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('test_account_netbanking_hdfc_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_HDFC_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_HDFC,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_hdfc',
                'gateway_terminal_id'   => 'demo_terminal_netbanking_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_netbanking_hdfc_terminal_pass'),
                'recurring'             => 0,
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
                'gateway'               => Gateway::BILLDESK,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_billdesk',
                'gateway_terminal_id'   => 'test_terminal_billdesk',
                'gateway_terminal_password' => Crypt::encrypt('test_account_billdesk_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::BILLDESK,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_billdesk',
                'gateway_terminal_id'   => 'demo_terminal_billdesk',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_billdesk_terminal_pass'),
                'recurring'             => 0,
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
                'gateway'               => Gateway::SHARP,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_sharp',
                'gateway_terminal_id'   => 'test_terminal_sharp',
                'gateway_terminal_password' => Crypt::encrypt('test_account_sharp_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::SHARP_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::SHARP,
                'card'                  => '1',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_sharp',
                'gateway_terminal_id'   => 'demo_terminal_sharp',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_sharp_terminal_pass'),
                'recurring'             => 0,
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
                'gateway'               => Gateway::MOBIKWIK,
                'card'                  => '0',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_merchant_mobikwik',
                'gateway_terminal_id'   => 'test_terminal_mobikwik',
                'gateway_terminal_password' => Crypt::encrypt('test_account_mobikwik_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::MOBIKWIK_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::MOBIKWIK,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_mobikwik',
                'gateway_terminal_id'       => 'demo_terminal_mobikwik',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_mobikwik_terminal_pass'),
                'recurring'             => 0,
                'created_at'                =>  time(),
                'updated_at'                =>  time(),
                )
            );
    }

    protected function createNetbankingKotakTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '22nP3sEf2tQ123',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => 'netbanking_kotak',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_kotak',
                'gateway_terminal_id'   => 'test_terminal_netbanking_kotak',
                'gateway_terminal_password' => Crypt::encrypt('test_account_netbanking_kotak_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_KOTAK_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => 'netbanking_kotak',
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_kotak',
                'gateway_terminal_id'   => 'demo_terminal_netbanking_kotak',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_netbanking_kotak_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
            )
        );
    }

    protected function createAmexTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '2eBIhcdN74TBMd',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AMEX,
                'card'                  => '1',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_merchant_amex',
                'gateway_terminal_id'   => 'test_terminal_amex',
                'gateway_terminal_password' => Crypt::encrypt('test_account_amex_terminal_pass'),
                'recurring'             => 0,
                'created_at'            =>  time(),
                'updated_at'            =>  time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::AMEX_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::AMEX,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_amex',
                'gateway_terminal_id'       => 'demo_terminal_amex',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_amex_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                =>  time(),
                'updated_at'                =>  time(),
                )
            );
    }

    protected function createPayzappTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2eBImcdG79tJdg',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_PAYZAPP,
                'card'                      => '0',
                'gateway_merchant_id'       => 'test_merchant_payzapp',
                'gateway_terminal_id'       => 'test_terminal_payzapp',
                'gateway_terminal_password' => Crypt::encrypt('test_account_payzapp_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '3fhDHSHD3425hV',
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_PAYZAPP,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_payzapp',
                'gateway_terminal_id'       => 'demo_terminal_payzapp',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_payzapp_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                =>  time(),
                'updated_at'                =>  time(),
                )
            );
    }

    protected function createUpiTerminals()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::UPI_ICICI_RAZORPAY_TERMINAL,
            'merchant_id'               => Account::DEMO_ACCOUNT,
            'gateway'                   => Gateway::UPI_ICICI,
            'card'                      => '0',
            'netbanking'                => '0',
            'upi'                       => '1',
            // This needs to be numeric
            'gateway_merchant_id'       => 'demo_merchant_upi_icici',
            'gateway_terminal_id'       => '1234',
            'gateway_terminal_password' => Crypt::encrypt('demo_account_upi_icici_terminal_pass'),
            'created_at'                =>  time(),
            'updated_at'                =>  time(),
        ]);
    }

    protected function createPayumoneyTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2byKhdVKZ9iDew',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_PAYUMONEY,
                'card'                      => '0',
                'gateway_merchant_id'       => 'test_merchant_payumoney',
                'gateway_terminal_id'       => 'test_terminal_payumoney',
                'gateway_terminal_password' => Crypt::encrypt('test_account_payumoney_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::PAYUMONEY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_PAYUMONEY,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_payumoney',
                'gateway_terminal_id'       => 'demo_terminal_payumoney',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_payumoney_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                =>  time(),
                'updated_at'                =>  time(),
            )
        );
    }

    protected function createOlamoneyTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2byKhdVKZ9iDex',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_OLAMONEY,
                'card'                      => '0',
                'gateway_terminal_id'       => 'test_terminal_olamoney',
                'gateway_terminal_password' => Crypt::encrypt('test_account_olamoney_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::OLAMONEY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_OLAMONEY,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => 'demo_terminal_olamoney',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_olamoney_terminal_pass'),
                'recurring'                 => 0,
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createAirtelmoneyTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2byKhdVKZ9iDey',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_AIRTELMONEY,
                'card'                      => '0',
                'gateway_terminal_id'       => 'test_terminal_airtelmoney',
                'gateway_terminal_password' => Crypt::encrypt('test_account_airtelmoney_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::AIRTELMONEY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_AIRTELMONEY,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => 'demo_terminal_airtelmoney',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_airtelmoney_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createFreechargeTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2baTHGYU9iDeXb',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_FREECHARGE,
                'card'                      => '0',
                'gateway_terminal_id'       => 'test_terminal_freecharge',
                'gateway_terminal_password' => Crypt::encrypt('test_account_freecharge_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::FREECHARGE_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_FREECHARGE,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => 'demo_terminal_freecharge',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_freecharge_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }
}
