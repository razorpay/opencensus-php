<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use RZP\Constants\Mode;
use RZP\Models\Pricing;
use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Payment\Gateway;
use Illuminate\Database\Seeder;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Balance;
use RZP\Models\Payment\Processor\Netbanking;

class DatabaseSeeder extends Seeder
{
    const RAZORPAY_ORG_ID = '100000razorpay';

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Eloquent::unguard();

        $this->seed();

//        $this->call('IinsTableSeeder');
//        $this->call('PermissionSeeder');
//        $this->call('GroupMapSeeder');
//        $this->call('WorkflowSeeder');
//        $this->call('TaxGroupAndTaxSeeder');
//        $this->call('DisputeReasonSeeder');
//        $this->call('BusinessBankingSeeder');
//        $this->call('BusinessBankingWorkflowsSeeder');
        //$this->call('CACStaticDataSeeder');
    }

    private function seed()
    {
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            $todayTime   = strtotime('today');
            $currentTime = time();

            foreach (Pricing\DefaultPlan::getPricingSeedData() as $pricing)
            {
                DB::table(Table::PRICING)->insert($pricing);
            }

            DB::table(Table::ORG)->insert(
                [
                    'id'               => self::RAZORPAY_ORG_ID,
                    'auth_type'        => 'google_auth',
                    'business_name'    => 'Razorpay',
                    'display_name'     => 'Razorpay Software Private Ltd',
                    'email'            => 'admin@razorpay.com',
                    'email_domains'    => 'razorpay.com',
                    'allow_sign_up'    => true,
                    'login_logo_url'   => null,
                    'main_logo_url'    => null,
                    'created_at'       => $currentTime,
                    'updated_at'       => $currentTime,
                    'cross_org_access' => true,
                    'custom_code'      => 'rzp',
                    'from_email'       => 'admin@razorpay.com',
                    'default_pricing_plan_id' => '1In3Yh5Mluj605',
                ]
            );

            DB::table(Table::ORG)->insert(
                [
                    'id'                => '6dLbNSpv5XbCOG',
                    'auth_type'         => 'password',
                    'business_name'     => 'HDFC',
                    'display_name'      => 'HDFC Bank Pvt Ltd',
                    'email'             => 'hdfc@bank.rzp.in',
                    'email_domains'     => 'hdfcbank.in',
                    'allow_sign_up'     => false,
                    'login_logo_url'    => null,
                    'main_logo_url'     => null,
                    'created_at'        => $currentTime,
                    'updated_at'        => $currentTime,
                    'custom_code'       => 'hdfc',
                    'from_email'        => 'admin@hdfcbank.com',
                    'default_pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
                ]
            );

            DB::table(Table::ORG_HOSTNAME)->insert(
                [
                    'id'                => '70I3fMI9AGKROX',
                    'org_id'            => self::RAZORPAY_ORG_ID,
                    'hostname'          => 'dashboard.razorpay.com',
                    'created_at'        => $currentTime,
                    'updated_at'        => $currentTime,
                ]
            );

            DB::table(Table::ORG_HOSTNAME)->insert(
                [
                    'id'                => '70I47LP6lyPYJR',
                    'org_id'            => self::RAZORPAY_ORG_ID,
                    'hostname'          => 'beta-dashboard.razorpay.com',
                    'created_at'        => $currentTime,
                    'updated_at'        => $currentTime,
                ]
            );

            DB::table(Table::ORG_HOSTNAME)->insert(
                [
                    'id'                => '70I6GMmOpMJp40',
                    'org_id'            => self::RAZORPAY_ORG_ID,
                    'hostname'          => 'dashboard.razorpay.in',
                    'created_at'        => $currentTime,
                    'updated_at'        => $currentTime,
                ]
            );

            DB::table(Table::ORG_HOSTNAME)->insert(
                [
                    'id'                => '70I6bfuaPQ72xa',
                    'org_id'            => '6dLbNSpv5XbCOG',
                    'hostname'          => 'dashboard-hdfc.razorpay.in',
                    'created_at'        => $currentTime,
                    'updated_at'        => $currentTime,
                ]
            );

            // DB::table(Table::ORG)->insert(
            //     [
            //         'id'                => 'org_6lFupOxpf36BY3',
            //         'auth_type'         => 'password',
            //         'business_name'     => 'ICICI',
            //         'display_name'      => 'ICICI Bank Pvt Ltd',
            //         'email'             => 'icici@icici.in',
            //         'email_domains'     => 'icici.in',
            //         'hostname'          => 'icici.in',
            //         'login_logo_url'    => null,
            //         'main_logo_url'     => null,
            //         'created_at'        => $currentTime,
            //         'updated_at'        => $currentTime,
            //     ]
            // );

            // DB::table(Table::ORG)->insert(
            //     [
            //         'id'                => 'org_6dLbNSpv5XbCOI',
            //         'auth_type'         => 'password',
            //         'business_name'     => 'BOB',
            //         'display_name'      => 'BOB Pvt Ltd',
            //         'email'             => 'bob@bob.in',
            //         'email_domains'     => 'bob.in',
            //         'hostname'          => 'bob.in',
            //         'login_logo_url'    => null,
            //         'main_logo_url'     => null,
            //         'created_at'        => $currentTime,
            //         'updated_at'        => $currentTime,
            //     ]
            // );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::NODAL_ACCOUNT,
                    'name'          => 'Razorpay Nodal Account',
                    'email'         => 'nodal@razorpay.com',
                    'category'      => '1234',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'nodal@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '10NodalAount',
                    )
                );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_101010',
                    'name'          => 'charge_at_will',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_202020',
                    'name'          => 'subscriptions',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_303030',
                    'name'          => 'bharat_qr',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_404040',
                    'name'          => 'virtual_accounts',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_505050',
                    'name'          => 'terminal_onboarding',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::FEATURE)->insert(
                array(
                    'id'            => 'feature_606060',
                    'name'          => 'marketplace',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                )
            );

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'   => Account::NODAL_ACCOUNT,
                    'contact_name'  => 'Razorpay Nodal Account',
                    'contact_email' => 'nodal@razorpay.com',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::NODAL_ACCOUNT,
                    'merchant_id'   => Account::NODAL_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::ATOM_ACCOUNT,
                    'name'          => 'Razorpay Atom Account',
                    'email'         => 'atom@razorpay.com',
                    'category'      => '1234',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'nodal@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '100AtomAount',
                ));

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'   => Account::ATOM_ACCOUNT,
                    'contact_name'  => 'Razorpay Atom Account',
                    'contact_email' => 'atom@razorpay.com',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::ATOM_ACCOUNT,
                    'merchant_id'   => Account::ATOM_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::API_FEE_ACCOUNT,
                    'name'          => 'Razorpay Fee Account',
                    'email'         => 'fees@razorpay.com',
                    'category'      => '1234',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'fees@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '1ApiFeeAount',
                    )
                );

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'   => Account::API_FEE_ACCOUNT,
                    'contact_name'  => 'Razorpay Fee Account',
                    'contact_email' => 'fees@razorpay.com',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::API_FEE_ACCOUNT,
                    'merchant_id'   => Account::API_FEE_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::TEST_ACCOUNT,
                    'name'          => 'Test Account',
                    'email'         => 'test@razorpay.com',
                    'category'      => '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'test@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'billing_label' => 'Test Account',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '100000000000',
                    )
                );

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'    => Account::TEST_ACCOUNT,
                    'contact_name'   => 'Test Account',
                    'contact_email'  => 'test@razorpay.com',
                    'contact_mobile' => '9876543210',
                    \RZP\Models\Merchant\Detail\Entity::BUSINESS_REGISTERED_ADDRESS  => 'Flat no 12, opp Adugodi Police Station',
                    \RZP\Models\Merchant\Detail\Entity::BUSINESS_REGISTERED_CITY  => 'Bangalore',
                    \RZP\Models\Merchant\Detail\Entity::BUSINESS_REGISTERED_STATE  => 'KA',
                    \RZP\Models\Merchant\Detail\Entity::BUSINESS_REGISTERED_PIN  => '560030',
                    \RZP\Models\Merchant\Detail\Entity::PROMOTER_PAN  => 'ABCPE1234F',
                    \RZP\Models\Merchant\Detail\Entity::PROMOTER_PAN_NAME  => 'John Doe',
                    'created_at'     => 1488306599, // 28/02/2017, 11:59:59 PM GMT+5:30; pre signup steps are required for people signing up on/after 01/03/2017
                    'updated_at'     => $currentTime,
                    )
                );

            DB::table(Table::USER)->insert([
                'id'             => '20000000000000',
                'name'           => 'Test User Account',
                'email'          => 'test@razorpay.com',
                'password'       => Hash::make('123456'),
                'contact_mobile' => '9999999999',
                'created_at'     => '1451606400', //1st Jan 2016.
                'updated_at'     => '1451606400'
            ]);

            DB::table(Table::USER)->insert([
                'id'             => '20000000000001',
                'name'           => 'Test User Account2',
                'email'          => 'test2@razorpay.com',
                'password'       => Hash::make('123456'),
                'contact_mobile' => '9999999999',
                'created_at'     => '1451606400',
                'updated_at'     => '1451606400'
            ]);

            DB::table(Table::MERCHANT_USERS)->insert([
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                    'user_id'     => '20000000000000',
                    'role'        => 'owner',
                    'created_at'  => '1451606400',
                    'updated_at'  => '1451606400'
                ],
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                    'user_id'     => '20000000000001',
                    'role'        => 'manager',
                    'created_at'  => '1451606400',
                    'updated_at'  => '1451606400'
                ]
            ]);

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::TEST_ACCOUNT,
                    'merchant_id'   => Account::TEST_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'balance'       => 100000,
                    'credits'       => 50000,
                    'on_hold'       => 10000,
                )
            );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::SHARED_ACCOUNT,
                    'name'          => 'Shared Account',
                    'email'         => 'shared@razorpay.com',
                    'category'      => '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'shared@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '100000Rarpay',
                    )
                );

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'   => Account::SHARED_ACCOUNT,
                    'contact_name'  => 'Shared Account',
                    'contact_email' => 'shared@razorpay.com',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::SHARED_ACCOUNT,
                    'merchant_id'   => Account::SHARED_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            => Account::DEMO_ACCOUNT,
                    'name'          => 'Demo Account',
                    'email'         => 'demo@razorpay.com',
                    'category'      => '1234',
                    'pricing_plan_id' => Pricing\DefaultPlan::FULL_PLAN_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    'transaction_report_email' => 'demo@razorpay.com',
                    'channel'       => 'kotak',
                    'risk_rating'   => 3,
                    'fee_bearer'    => 0,
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'invoice_code'  => '100DemoAount',
                    )
                );

            DB::table(Table::MERCHANT_DETAIL)->insert(
                array(
                    'merchant_id'   => Account::DEMO_ACCOUNT,
                    'contact_name'  => 'Demo Account',
                    'contact_email' => 'demo@razorpay.com',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            => Account::DEMO_ACCOUNT,
                    'merchant_id'   => Account::DEMO_ACCOUNT,
                    'type'          => Balance\Type::PRIMARY,
                    'channel'       => Balance\AccountType::SHARED,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                    )
                );

            DB::table(Table::SCHEDULE)->insert(
                [
                    'id'          => '30000000000000',
                    'name'        => 'Basic T+3',
                    'merchant_id' => '100000Razorpay',
                    'period'      => 'daily',
                    'interval'    => 1,
                    'hour'        => 10,
                    'delay'       => 3,
                    'created_at'  => $currentTime,
                    'updated_at'  => $currentTime,
                ]
            );

            DB::table(Table::SCHEDULE_TASK)->insert(
                [
                    'merchant_id' => '10000000000000',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant',
                    'type'        => 'settlement',
                    'schedule_id' => '30000000000000',
                    'next_run_at' => $currentTime + 259200,
                    'created_at'  => $currentTime,
                    'updated_at'  => $currentTime,
                ]
            );

            if ($name === Mode::TEST)
            {
                $this->createTestTerminals();

                $this->createLocalCustomer();

                $this->createGlobalCustomer();

                $this->createVpas();

                $this->createDevice();
            }

            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'    => Account::DEMO_ACCOUNT,
                    'banks'          => '[]',
                    'disabled_banks' => '[]',
                    'paytm'          => '1',
                    'aeps'           => '1',
                    'olamoney'       => '1',
                    'freecharge'     => '1',
                    'emandate'       => '1',
                    'mobikwik'       => '1',
                    'payzapp'        => '1',
                    'payumoney'      => '1',
                    'airtelmoney'    => '1',
                    'openwallet'     => '1',
                    'jiomoney'       => '1',
                    'sbibuddy'       => '1',
                    'card'           => '1',
                    'upi'            => '1',
                    'created_at'     => $currentTime,
                    'updated_at'     => $currentTime
                )
            );

            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   => Account::TEST_ACCOUNT,
                    'banks'         => '[]',
                    'disabled_banks'=> '[]',
                    'paytm'         => '1',
                    'aeps'          => '1',
                    'paypal'        => '1',
                    'mobikwik'      => '1',
                    'olamoney'      => '1',
                    'freecharge'    => '1',
                    'emandate'      => '1',
                    'payzapp'       => '1',
                    'payumoney'     => '1',
                    'airtelmoney'   => '1',
                    'amazonpay'     => '1',
                    'openwallet'    => '1',
                    'jiomoney'      => '1',
                    'sbibuddy'      => '1',
                    'card'          => '1',
                    'emi'           => '1',
                    'upi'           => '1',
                    'bank_transfer' => '1',
                    'cardless_emi'  => '1',
                    'paylater'      => '1',
                    'nach'          => '1',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime
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
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                )
            );

            DB::table(Table::ADMIN)->insert([
                [
                    'id'                  => '6dLbNSpv5Ybbbb',
                    'email'               => 'rzp@hdfcbank.in',
                    'name'                => 'Test HDFC Account',
                    'username'            => 'rzp',
                    // Hash::make(123456)
                    'password'            => '$2y$10$hq9FiWfdGNQYrMLhFIcHFeTugK3prV0Y6ghWC5AKuDQKNVS4Xx4SG',
                    'org_id'              => '6dLbNSpv5XbCOG',
                    'employee_code'       => '010',
                    'department_code'     => 'ADMIN',
                    'branch_code'         => 'HDFC010',
                    'supervisor_code'     => '001',
                    'location_code'       => 'BLR',
                    'last_login_at'       => $todayTime,
                    'created_at'          => $todayTime,
                    'updated_at'          => $todayTime,
                    'allow_all_merchants' => true,
                ],
            ]);

            DB::table(Table::ORG_FIELD_MAP)->insert([
                [
                    'id'          => '7NamQFIGFyyNdc',
                    'org_id'      => '100000razorpay',
                    'entity_name' => 'admin',
                    'fields'      => 'name,email,allow_all_merchants,disabled,oauth_access_token,oauth_provider_id,roles,groups',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
                [
                    'id'          => '7NawjzGBBIX6Ar',
                    'org_id'      => '6dLbNSpv5XbCOG',
                    'entity_name' => 'admin',
                    'fields'      => 'name,username,email,password,password_confirmation,employee_code,department_code,branch_code,location_code,supervisor_code,allow_all_merchants,disabled,roles,groups',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
                [
                    'id'          => '7NayAS7Iz2aMyi',
                    'org_id'      => '6dLbNSpv5XbCOG',
                    'entity_name' => 'admin_lead',
                    'fields'      => 'channel_code,crm_next_no,db_token_no,branch_lts_no,branch_code,source_code,promo_code,lg_code,lc_ro_code,mrm_code,merchant_type,mcc_category,mcc_code,merchant_name,contact_name,contact_email,dba_name',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
                [
                    'id'          => '7Nb15NvBMGKP2k',
                    'org_id'      => '100000razorpay',
                    'entity_name' => 'admin_lead',
                    'fields'      => 'merchant_name,contact_name,contact_email,dba_name',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ],
            ]);

            DB::table(Table::ADMIN)->insert([
                [
                    'id'                  => '6dLbNSpv5Ycccc',
                    'email'               => 'rishabh.pugalia@razorpay.com',
                    'name'                => 'Rishabh Pugalia',
                    'username'            => 'rishabhp',
                    'org_id'              => self::RAZORPAY_ORG_ID,
                    'employee_code'       => '001',
                    'branch_code'         => 'RZP001',
                    'department_code'     => 'ADMIN',
                    'supervisor_code'     => '001',
                    'location_code'       => 'BLR',
                    'last_login_at'       => null,
                    'created_at'          => $todayTime,
                    'updated_at'          => $todayTime,
                    'allow_all_merchants' => true,
                ]
            ]);

            // for curl/postman testing purposes
            DB::table(Table::ADMIN_TOKEN)->insert([
                    'id'                  => '7gyptrWlOKu6z9',
                    'admin_id'            => '6dLbNSpv5Ycccc',
                    'token'               => '1234567',
                    'created_at'          => time(),
                    'updated_at'          => time(),
            ]);

            DB::table(Table::ROLE)->insert([
                // RZP
                [
                    'id'            => '6dLbNSpv5XbC5E',
                    'name'          => 'SuperAdmin',
                    'description'   => 'Super Administrator',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                // HDFC
                [
                    'id'            => '6dLbNSpv5XbC5F',
                    'name'          => 'SuperAdmin',
                    'description'   => 'Super Administrator',
                    'org_id'        => '6dLbNSpv5XbCOG',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '7bdvjyhVRgcfCe',
                    'name'          => 'Checker One',
                    'description'   => 'Checker One',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '7bdvYPtRHEw3zB',
                    'name'          => 'Checker Two',
                    'description'   => 'Checker Two',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '7bdyCqxHR23Y9u',
                    'name'          => 'Checker Three',
                    'description'   => 'Checker Three',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '7bdxOwCk3Eyu0R',
                    'name'          => 'Maker One',
                    'description'   => 'Maker One',
                    'org_id'        => self::RAZORPAY_ORG_ID,
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],

            ]);

            DB::table(Table::ROLE_MAP)->insert([
                // RZP
                [
                    'role_id'       => '6dLbNSpv5XbC5E',
                    'entity_id'     => '6dLbNSpv5Ycccc',
                    'entity_type'   => 'admin',
                ],
                // HDFC
                [
                    'role_id'       => '6dLbNSpv5XbC5F',
                    'entity_id'     => '6dLbNSpv5Ybbbb',
                    'entity_type'   => 'admin',
                ],
                // [
                //     'role_id'       => '6dLbNSpv5XbC5G',
                //     'entity_id'     => '6dLbNSpv5Ybbbc',
                //     'entity_type'   => 'admin',
                // ]
            ]);

            DB::table(Table::GROUP)->insert([
                [
                    'id'            => '6euDnqS4zQR4ke',
                    'name'          => 'Karnataka',
                    'description'   => 'Karnataka Group',
                    'org_id'        => '6dLbNSpv5XbCOG',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '6euDnqS4zQR4kf',
                    'name'          => 'Bangalore',
                    'description'   => 'Bangalore Group',
                    'org_id'        => '6dLbNSpv5XbCOG',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ],
                [
                    'id'            => '6euDnqS4zQR4kg',
                    'name'          => 'Indiranagar',
                    'description'   => 'Indiranagar Group',
                    'org_id'        => '6dLbNSpv5XbCOG',
                    'created_at'    => $currentTime,
                    'updated_at'    => $currentTime,
                ]
            ]);

            DB::table(Table::GROUP_MAP)->insert([
                // G1 -> A1
                [
                    'group_id'      => '6euDnqS4zQR4ke',
                    'entity_id'     => '6dLbNSpv5Ybbbb',
                    'entity_type'   => 'admin',
                ],
                // G2 -> G1
                [
                    'group_id'      => '6euDnqS4zQR4ke',
                    'entity_id'     => '6euDnqS4zQR4kf',
                    'entity_type'   => 'group',
                ],
                // G3 -> G2
                [
                    'group_id'      => '6euDnqS4zQR4kf',
                    'entity_id'     => '6euDnqS4zQR4kg',
                    'entity_type'   => 'group',
                ],
                // G2 -> A2
                // [
                //     'group_id'      => '6euDnqS4zQR4kf',
                //     'entity_id'     => '6dLbNSpv5Ybbbc',
                //     'entity_type'   => 'admin',
                // ]
            ]);

            DB::table(Table::MERCHANT_MAP)->insert([
                [
                    'merchant_id'   => '10000000000000',
                    'entity_id'     => '6dLbNSpv5Ycccc',
                    'entity_type'   => 'admin',
                ]
            ]);
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

    protected function createGlobalCustomer()
    {
        DB::table(Table::CUSTOMER)->insert(
            array(
                'id'                    => 'TestGloblCstmr',
                'merchant_id'           => Account::SHARED_ACCOUNT,
                'name'                  => 'Global Citizen',
                'contact'               => '+919876543210',
                'email'                 => 'test4@razorpay.com',
                'notes'                 => '{}',
                'active'                => true,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createVpas()
    {
        DB::table(Table::VPA)->insert(
            array(
                'id'                    => 'TestSenderVpa',
                'username'              => 'sender',
                'handle'                => 'razor',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );

        DB::table(Table::VPA)->insert(
            array(
                'id'                    => 'TestReceivrVpa',
                'username'              => 'receiver',
                'handle'                => 'razor',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createDevice()
    {
        DB::table(Table::DEVICE)->insert(
            array(
                'id'                    => 'TestNokia3310',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'customer_id'           => 'TestGloblCstmr',
                'imei'                  => 'TestImeiValue',
                'status'                => 'verified',
                'auth_token'            => 'auth_to_be_okay',
                'verification_token'    => 'verin_this_together',
                'upi_token'             => 'upi_dont_need_to',
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'gateway_acquirer'      => 'hdfc',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_hdfc',
                'gateway_terminal_id'   => 'test_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('test_account_hdfc_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 1,
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25f6uN5S1Zak',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::WALLET_PAYPAL,
                'card'                  => '0',
                'gateway_merchant_id'   => 'A6BJBXR5ABB2G',
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 0,
                'mode'                  => 2,
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25f6u1Zgsmpl',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::PAYLATER,
                'gateway_acquirer'      => 'getsimpl',
                'card'                  => '0',
                'gateway_merchant_id'   => '813074bab6c38ed91fe6ff65e4cd585b',
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 0,
                'paylater'              => 1,
                'mode'                  => 2,
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25ficipayltr',
                'merchant_id'           => Account::SHARED_ACCOUNT,
                'gateway'               => Gateway::PAYLATER,
                'gateway_acquirer'      => 'icic',
                'card'                  => '0',
                'gateway_merchant_id'   => 'test_merchant_id',
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 0,
                'paylater'              => 1,
                'mode'                  => 2,
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1n25f6uN5S1Z7c',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'gateway_acquirer'      => 'hdfc',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_hdfc',
                'gateway_terminal_id'   => 'test_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('test_account_hdfc_terminal_pass'),
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 65,
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 1,
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1pnP3sEf2tQsm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AXIS_MIGS,
                'gateway_acquirer'      => 'axis',
                'card'                  => '1',
                'gateway_merchant_id'   => 'test_merchant_axis_migs',
                'gateway_terminal_id'   => 'test_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('test_account_axis_migs_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 1,
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '1VwJebUIU7hIhU',
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'gateway_acquirer'      => 'hdfc',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_hdfc',
                'gateway_terminal_id'   => 'demo_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 1,
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::AXIS_MIGS,
                'gateway_acquirer'      => 'axis',
                'card'                  => '1',
                'gateway_merchant_id'   => 'demo_merchant_axis_migs',
                'gateway_terminal_id'   => 'shared_terminal_axis_migs',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_axis_migs_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::FIRST_DATA_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::FIRST_DATA,
                'gateway_acquirer'          => 'icic',
                'card'                      => '1',
                'gateway_merchant_id'       => 'demo_merchant_first_data',
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                'type'                  => 1,
                )
            );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => 'djfksjfksjfdkj',
                'merchant_id'           => Account::SHARED_ACCOUNT,
                'gateway'               => Gateway::HDFC,
                'gateway_acquirer'      => 'hdfc',
                'emi'                   => '1',
                'shared'                => '1',
                'emi_duration'          => 9,
                'gateway_merchant_id'   => 'test_merchant_emi',
                'gateway_terminal_id'   => 'shared_terminal_hdfc',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                )
            );

        DB::table(Table::KEY)->insert(
            array(
                'id'            => Account::TEST_ACCOUNT_KEY_ID,
                'merchant_id'   => Account::TEST_ACCOUNT,
                'secret'        => Crypt::encrypt('thisissupersecret'),
                'created_at'    => time(),
                'updated_at'    => time()
                )
            );

        DB::table(Table::KEY)->insert(
            array(
                'id'            => Account::DEMO_ACCOUNT_KEY_ID,
                'merchant_id'   => Account::DEMO_ACCOUNT,
                'secret'        => Crypt::encrypt('thisissupersecret'),
                'created_at'    => time(),
                'updated_at'    => time()
                )
            );

        $this->createAmexTerminals();
        $this->createCybersourceTerminals();
        $this->createHitachiGatewayTerminals();
        $this->createBilldeskGatewayTerminals();
        $this->createNetbankingBobTerminals();
        $this->createNetbankingVijayaTerminals();
        $this->createNetbankingHdfcTerminals();
        $this->createNetbankingCorporationTerminals();
        $this->createMobikwikTerminals();
        $this->createPayzappTerminals();
        $this->createPayumoneyTerminals();
        $this->createSharpGatewayTerminals();
        $this->createNetbankingIdfcTerminals();
        $this->createNetbankingKotakTerminals();
        $this->createNetbankingIciciTerminals();
        $this->createNetbankingCanaraTerminal();
        $this->createNetbankingAirtelTerminals();
        $this->createNetbankingAllahabadTerminals();
        $this->createNetbankingObcTerminal();
        $this->createNetbankingAxisTerminal();
        $this->createNetbankingUbiTerminal();
        $this->createNetbankingScbTerminal();
        $this->createNetbankingJkbTerminal();
        $this->createNetbankingEquitasTerminal();
        $this->createNetbankingFederalTerminal();
        $this->createNetbankingIndusindTerminal();
        $this->createNetbankingPnbTerminal();
        $this->createNetbankingCubTerminal();
        $this->createNetbankingIbkTerminal();
        $this->createNetbankingIdbiTerminal();
        $this->createNetbankingSbiTerminal();
        $this->createNetbankingYesbTerminal();
        $this->createOlamoneyTerminals();
        $this->createUpiTerminals();
        $this->createAirtelmoneyTerminals();
        $this->createAmazonpayTerminals();
        $this->createFreechargeTerminals();
        $this->createJiomoneyTerminals();
        $this->createSbibuddyTerminals();
        $this->createOpenwalletTerminals();
        $this->createRazorpaywalletTerminals();
        $this->createVodafoneMpesaTerminal();
        $this->createNetbankingSibTerminal();
        $this->createNetbankingCbiTerminal();
        $this->createNetbankingRblTerminal();
        $this->createNetbankingCsbTerminal();
        $this->createEbsTerminal();
        $this->createEnachRblTerminal();
        $this->createEnachNetbankingNpciTerminal();
        $this->createAepsTerminal();
        $this->createHitachiGatewayMotoTerminal();
        $this->createEnstageTerminal();
        $this->createCardlessEmiTerminal();
        $this->createPayLaterTerminal();
        $this->createNetbankingKvbTerminal();
        $this->createNetbankingSvcTerminal();
        $this->createNetbankingJsbTerminal();
        $this->createNetbankingIobTerminal();
        $this->createNetbankingFsbTerminal();
        $this->createPayuTerminal();
        $this->createCashfreeTerminal();
        $this->createZaakpayTerminal();
        $this->createPinelabsTerminal();
        $this->createIngenicoTerminal();
        $this->createBilldeskOptimizerTerminal();
        $this->createNetbankingDcbTerminal();
        $this->createTwidTerminal();
        $this->createCcavenueTerminal();
        $this->createCheckoutDotComTerminal();
        $this->createEmerchantpayTerminal();
    }

    protected function createNetbankingCorporationTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => '22nP3sEf2tQco1',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_CORPORATION,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_corporation',
                'gateway_secure_secret'     => Crypt::encrypt('test_account_netbanking_corp_secret'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_CORPORATION_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_CORPORATION,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_corporation',
                'gateway_secure_secret' => Crypt::encrypt('test_account_netbanking_corp_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingAllahabadTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => '22nP3sEf2tQf0p',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_ALLAHABAD,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_allahabad',
                'gateway_secure_secret'     => Crypt::encrypt('test_account_netbanking_alla_secret'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );
         DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_ALLAHABAD_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_ALLAHABAD,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_allahabad',
                'gateway_secure_secret' => Crypt::encrypt('test_account_netbanking_alla_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingIdfcTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => '22nP3sEf2tQco2',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_IDFC,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_idfc',
                'gateway_secure_secret'     => Crypt::encrypt('test_account_netbanking_idfb_secret'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_IDFC_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_IDFC,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_idfc',
                'gateway_secure_secret' => Crypt::encrypt('test_account_netbanking_idfb_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
                )
            );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_HDFC_REC_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_HDFC,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_hdfc_recurring',
                'recurring'                 => 1,
                'emandate'                  => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
                'type'                      => 6,
            ]
        );
    }

    protected function createNetbankingBobTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '22BOfBarodaRm8',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_BOB,
                'netbanking'            => '1',
                'corporate'             => '2',
                'gateway_merchant_id'   => 'test_merchant_netbanking_bob',
                'gateway_secure_secret' => Crypt::encrypt('test_account_netbanking_bob_hash_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createNetbankingVijayaTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_VIJAYA_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_VIJAYA,
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_vijaya',
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createCybersourceTerminals()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                        => '1VwJebUIU7hIhd',
            'merchant_id'               => Account::TEST_ACCOUNT,
            'gateway'                   => Gateway::CYBERSOURCE,
            'gateway_acquirer'          => 'hdfc',
            'card'                      => '1',
            'gateway_merchant_id'       => 'test_merchant_cybersource',
            'gateway_terminal_id'       => 'test_terminal_cybersource',
            'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
            'recurring'                 => 1,
            'created_at'                => time(),
            'updated_at'                => time(),
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::CYBERSOURCE_HDFC_TERMINAL,
            'merchant_id'               => Account::DEMO_ACCOUNT,
            'gateway'                   => Gateway::CYBERSOURCE,
            'gateway_acquirer'          => 'hdfc',
            'card'                      => '1',
            'gateway_merchant_id'       => 'demo_merchant_cybersource',
            'gateway_terminal_id'       => 'demo_terminal_cybersource',
            'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
            'recurring'                 => 1,
            'created_at'                => time(),
            'updated_at'                => time(),
        ]);
    }

    protected function createHitachiGatewayTerminals()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::HITACHI_TERMINAL,
            'merchant_id'               => Account::TEST_ACCOUNT,
            'gateway'                   => Gateway::HITACHI,
            'gateway_acquirer'          => 'rbl',
            'card'                      => 1,
            'gateway_merchant_id'       => 'test_merchant_hitachi',
            'gateway_secure_secret'     => Crypt::encrypt('test_hitachi_secure_secret'),
            'gateway_terminal_password' => Crypt::encrypt('test_hitachi_secure_secret2'),
            'recurring'                 => 1,
            'created_at'                => time(),
            'updated_at'                => time()
        ]);
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
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
                'recurring'             => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
                )
            );
    }

    protected function createNetbankingKotakTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => '22nP3sEf2tQ123',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_KOTAK,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_kotak',
                'gateway_terminal_id'   => 'test_terminal_netbanking_kotak',
                'gateway_terminal_password' => Crypt::encrypt('test_account_netbanking_kotak_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_KOTAK_TERMINAL,
                'merchant_id'           => Account::DEMO_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_KOTAK,
                'card'                  => '0',
                'netbanking'            => '1',
                'corporate'             => '1',
                'gateway_merchant_id'   => 'demo_merchant_netbanking_kotak',
                'gateway_terminal_id'   => 'demo_terminal_netbanking_kotak',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_netbanking_kotak_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createNetbankingIciciTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_ICICI_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_ICICI,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_icici',
                'gateway_merchant_id2'      => 'test_submerchant_netbanking_icici',
                'gateway_secure_secret'     => Crypt::encrypt('test_netbanking_master_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_ICICI_REC_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_ICICI,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_icici_recurring',
                // 'gateway_merchant_id2'      => 'test_submerchant_netbanking_icici',
                // 'gateway_secure_secret'     => Crypt::encrypt('test_netbanking_master_terminal_pass'),
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
                'type'                      => 6,
            ]
        );
    }

    protected function createNetbankingCanaraTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_CANARA_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_CANARA,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_merchant_netbanking_canara',
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );
    }


    protected function createNetbankingAirtelTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_AIRTEL_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_AIRTEL,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_airtel',
                'gateway_secure_secret' => Crypt::encrypt('test_airtel_terminal_salt'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingObcTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_OBC_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_OBC,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_oriental',
                'gateway_secure_secret' => Crypt::encrypt('test_oriental_terminal_salt'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingEquitasTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_ESFB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_EQUITAS,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_equitas',
                'gateway_secure_secret' => Crypt::encrypt('test_equitas_terminal_salt'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingSbiTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_SBI_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_SBI,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'RAZORPAY',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_SBI_REC_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_SBI,
                'card'                      => '0',
                'netbanking'                => '0',
                'emandate'                  => '1',
                'gateway_merchant_id'       => 'RAZORPAY',
                'gateway_secure_secret'     => Crypt::encrypt('test_secure_secret'),
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
                'type'                      => 6,
            ]
        );
    }

    protected function createNetbankingYesbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_YESB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_YESB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'RAZORPAY',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingAxisTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_AXIS_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_AXIS,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_axis',
                'gateway_secure_secret' => Crypt::encrypt('test_netbanking_axis_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                    'id'                        => Terminal\Shared::NETBANKING_AXIS_REC_TERMINAL,
                    'merchant_id'               => Account::TEST_ACCOUNT,
                    'gateway'                   => Gateway::NETBANKING_AXIS,
                    'card'                      => '0',
                    'netbanking'                => '0',
                    'emandate'                  => '1',
                    'gateway_merchant_id'       => 'test_merchant_netbanking_axis_recurring',
                    'recurring'                 => 1,
                    'created_at'                => time(),
                    'updated_at'                => time(),
                    'type'                      => 6,
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NACH_CITI_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NACH_CITI,
                'nach'                      => '1',
                'gateway_merchant_id'       => 'NACH00000000010000',
                'gateway_merchant_id2'      => 'NACH00000000010000',
                'gateway_acquirer'          => 'RATN0TREASU',
                'gateway_access_code'       => 'dummy',
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
                'type'                      => 2,
            ]
        );
    }

    protected function createNetbankingUbiTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_UBI_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_UBI,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_ubi',
                'gateway_secure_secret' => Crypt::encrypt('test_netbanking_ubi_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingScbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_SCB_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_SCB,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_netbanking_scb_merchant_id',
                'gateway_secure_secret'     => Crypt::encrypt('test_netbanking_scb_encryption_key'),
                'gateway_secure_secret2'    => Crypt::encrypt('test_netbanking_scb_decryption_key'),
                'gateway_terminal_password' => Crypt::encrypt('test_netbanking_scb_hash_salt'),
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );
    }

    protected function createNetbankingJkbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::NETBANKING_JKB_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::NETBANKING_JKB,
                'card'                      => '0',
                'netbanking'                => '1',
                'gateway_merchant_id'       => 'test_netbanking_jkb_merchant_id',
                'recurring'                 => 0,
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );
    }

    protected function createNetbankingFederalTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_FEDERAL_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_FEDERAL,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_federal',
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingIndusindTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_INDUSIND_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_INDUSIND,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_indusind',
                'gateway_secure_secret' => Crypt::encrypt('test_netbanking_indusind_terminal_pass'),
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createNetbankingPnbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                  => Terminal\Shared::NETBANKING_PNB_TERMINAL,
                'merchant_id'         => Account::TEST_ACCOUNT,
                'gateway'             => Gateway::NETBANKING_PNB,
                'card'                => '0',
                'netbanking'          => '1',
                'gateway_merchant_id' => 'test_merchant_netbanking_pnb',
                'recurring'           => 0,
                'created_at'          => time(),
                'updated_at'          => time(),
            )
        );
    }

    protected function createTwidTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                     => Terminal\Shared::TWID_TERMINAL,
                'merchant_id'            => Account::TEST_ACCOUNT,
                'gateway'                => Gateway::TWID,
                'card'                   => '0',
                'netbanking'             => '0',
                'app'                    => '1',
                'enabled_apps'           => '["twid"]',
                'gateway_merchant_id'    => 'gateway_merchant_id',
                'gateway_secure_secret'  => Crypt::encrypt('gateway_secure_secret'),
                'gateway_secure_secret2' => Crypt::encrypt('gateway_secure_secret2'),
                'recurring'              => 0,
                'created_at'             => time(),
                'updated_at'             => time(),
            )
        );
    }

    protected function createNetbankingCubTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                  => Terminal\Shared::NETBANKING_CUB_TERMINAL,
                'merchant_id'         => Account::TEST_ACCOUNT,
                'gateway'             => Gateway::NETBANKING_CUB,
                'card'                => '0',
                'netbanking'          => '1',
                'gateway_merchant_id'        => 'test_merchant_netbanking_CUB',
                'gateway_terminal_password'  => Crypt::encrypt('test_terminal_password'),
                'gateway_terminal_password2' => Crypt::encrypt('test_terminal_password2'),
                'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
                'gateway_secure_secret2'     => Crypt::encrypt('test_secure_secret2'),
                'recurring'           => 0,
                'created_at'          => time(),
                'updated_at'          => time(),
            )
        );
    }

    protected function createNetbankingIbkTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                         => Terminal\Shared::NETBANKING_IBK_TERMINAL,
                'merchant_id'                => Account::TEST_ACCOUNT,
                'gateway'                    => Gateway::NETBANKING_IBK,
                'card'                       => '0',
                'netbanking'                 => '1',
                'gateway_merchant_id'        => 'test_merchant_netbanking_IBK',
                'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
                'recurring'                  => 0,
                'created_at'                 => time(),
                'updated_at'                 => time(),
                )
        );
    }
    protected function createNetbankingIdbiTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                  => Terminal\Shared::NETBANKING_IDBI_TERMINAL,
                'merchant_id'         => Account::TEST_ACCOUNT,
                'gateway'             => Gateway::NETBANKING_IDBI,
                'card'                => '0',
                'netbanking'          => '1',
                'gateway_merchant_id'        => 'test_merchant_netbanking_IDBI',
                'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
                'recurring'           => 0,
                'created_at'          => time(),
                'updated_at'          => time(),
            )
        );
    }

    protected function createNetbankingSibTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_SIB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_SIB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_sib',
                'gateway_secure_secret' => Crypt::encrypt('test_key'),
                'recurring'             => 0,
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createNetbankingCbiTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                    => Terminal\Shared::NETBANKING_CBI_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_CBI,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_merchant_netbanking_cbi',
                'gateway_secure_secret' => Crypt::encrypt('test_key'),
                'recurring'             => 0,
                'enabled_banks'         => '["CBIN"]',
                'created_at'            => time(),
                'updated_at'            => time(),
            )
        );
    }

    protected function createAmexTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => '2eBIhcdN74TBMd',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AMEX,
                'gateway_acquirer'      => 'amex',
                'card'                  => '1',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_merchant_amex',
                'gateway_terminal_id'   => 'test_terminal_amex',
                'gateway_terminal_password' => Crypt::encrypt('test_account_amex_terminal_pass'),
                'recurring'             => 1,
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::AMEX_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::AMEX,
                'gateway_acquirer'          => 'amex',
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'demo_merchant_amex',
                'gateway_terminal_id'       => 'demo_terminal_amex',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_amex_terminal_pass'),
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
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
                'recurring'                 => 1,
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
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
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
            'created_at'                => time(),
            'updated_at'                => time(),
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::UPI_MINDGATE_RAZORPAY_TERMINAL,
            'merchant_id'               => Account::DEMO_ACCOUNT,
            'gateway'                   => Gateway::UPI_MINDGATE,
            'card'                      => '0',
            'netbanking'                => '0',
            'upi'                       => '1',
            'gateway_merchant_id'       => 'razorpay_upi_mindgate',
            'gateway_terminal_id'       => '1234',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            'gateway_terminal_password' => Crypt::encrypt('demo_account_upi_mindgate_terminal_pass'),
            'created_at'                => time(),
            'updated_at'                => time(),
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                          => '101UPIMindgate',
            'merchant_id'                 => Account::SHARED_ACCOUNT,
            'gateway'                     => Gateway::UPI_MINDGATE,
            'card'                        => '0',
            'netbanking'                  => '0',
            'upi'                         => '1',
            'gateway_merchant_id'         => 'HDFCTEST',
            'gateway_terminal_id'         => '1234',
            'gateway_terminal_password'   => Crypt::encrypt('shared_account_upi_mindgate_terminal_pass'),
            'type'                        => '65537',
            'virtual_upi_root'            => 'rzpy.',
            'virtual_upi_merchant_prefix' => 'payto00000',
            'virtual_upi_handle'          => 'hdfcbank',
            'created_at'                  => time(),
            'updated_at'                  => time(),
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                          => Terminal\Shared::UPI_ICICI_VPA_TERMINAL,
            'merchant_id'                 => Account::SHARED_ACCOUNT,
            'gateway'                     => Gateway::UPI_ICICI,
            'card'                        => '0',
            'netbanking'                  => '0',
            'upi'                         => '1',
            'gateway_merchant_id'         => '190127',
            'gateway_merchant_id2'        => 'rzr.payto00000@icici',
            'gateway_terminal_id'         => '1234',
            'gateway_terminal_password'   => Crypt::encrypt('shared_account_upi_icici_terminal_pass'),
            'type'                        => '65537',
            'virtual_upi_root'            => 'rzr.',
            'virtual_upi_merchant_prefix' => 'payto00000',
            'virtual_upi_handle'          => 'icici',
            'created_at'                  => time(),
            'updated_at'                  => time(),
       ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::UPI_AXIS_RAZORPAY_TERMINAL,
            'merchant_id'               => Account::DEMO_ACCOUNT,
            'gateway'                   => Gateway::UPI_AXIS,
            'card'                      => '0',
            'netbanking'                => '0',
            'upi'                       => '1',
            'gateway_merchant_id'       => 'TSTMERCHI',
            'gateway_merchant_id2'      => 'TSTMERCHIAPP',
            'vpa'                       => 'a@axis',
            'created_at'                => time(),
            'updated_at'                => time(),
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL,
            'merchant_id'               => Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::UPI_SBI,
            'card'                      => '0',
            'netbanking'                => '0',
            'upi'                       => '1',
            'gateway_merchant_id'       => 'upi_sbi_merchant_id',
            'gateway_merchant_id2'      => 'razorpay@sbibank',
            'created_at'                => time(),
            'updated_at'                => time(),
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
                'recurring'                 => 1,
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
                'recurring'                 => 1,
                'created_at'                => time(),
                'updated_at'                => time(),
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
                'recurring'                 => 1,
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
                'recurring'                 => 1,
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

    protected function createAmazonpayTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::AMAZONPAY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_AMAZONPAY,
                'gateway_merchant_id'       => 'gateway_merchant_id',
                'gateway_terminal_password' => Crypt::encrypt('amazonpay_secure_secret'),
                'gateway_access_code'       => 'gateway_access_key',
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createJiomoneyTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => '6tUImiItg84AzK',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_JIOMONEY,
                'card'                      => '0',
                'gateway_terminal_id'       => 'test_terminal_jiomoney',
                'gateway_terminal_password' => Crypt::encrypt('test_account_jiomoney_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::JIOMONEY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_JIOMONEY,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => 'demo_terminal_jiomoney',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_jiomoney_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
        );
    }

    protected function createSbibuddyTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => '6tUImiIsb1budy',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_SBIBUDDY,
                'card'                      => '0',
                'gateway_terminal_id'       => 'test_terminal_sbibuddy',
                'gateway_terminal_password' => Crypt::encrypt('test_account_sbibuddy_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            ]
        );

        DB::table(Table::TERMINAL)->insert(
            [
                'id'                        => Terminal\Shared::SBIBUDDY_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_SBIBUDDY,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => 'demo_terminal_sbibuddy',
                'gateway_terminal_password' => Crypt::encrypt('demo_account_sbibuddy_terminal_pass'),
                'created_at'                => time(),
                'updated_at'                => time(),
            ]
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

    protected function createOpenwalletTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '2baTHP2a9iDeXr',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_OPENWALLET,
                'card'                      => '0',
                'gateway_terminal_id'       => null,
                'gateway_terminal_password' => null,
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::OPENWALLET_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_OPENWALLET,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => null,
                'gateway_terminal_password' => null,
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createRazorpaywalletTerminals()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => '3cbUIQ3b0jEfYs',
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_RAZORPAYWALLET,
                'card'                      => '0',
                'gateway_terminal_id'       => null,
                'gateway_terminal_password' => null,
                'created_at'                => time(),
                'updated_at'                => time(),
                'category'                  => 1000,
                'shared'                    => '1',
            )
        );

        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::RAZORPAYWALLET_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::DEMO_ACCOUNT,
                'gateway'                   => Gateway::WALLET_RAZORPAYWALLET,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_terminal_id'       => null,
                'gateway_terminal_password' => null,
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createVodafoneMpesaTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                        => Terminal\Shared::MPESA_RAZORPAY_TERMINAL,
                'merchant_id'               => Account::TEST_ACCOUNT,
                'gateway'                   => Gateway::WALLET_MPESA,
                'card'                      => '0',
                'netbanking'                => '0',
                'gateway_merchant_id'       => 'random_merchant_id',
                'gateway_merchant_id2'      => 'random_merchant_id2',
                'gateway_secure_secret'     => Crypt::encrypt('demo_account_mpesa_secure_secret'),
                'created_at'                => time(),
                'updated_at'                => time(),
            )
        );
    }

    protected function createNetbankingRblTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                   => Terminal\Shared::NETBANKING_RBL_TERMINAL,
                'merchant_id'          => Account::TEST_ACCOUNT,
                'gateway'              => Gateway::NETBANKING_RBL,
                'card'                 => '0',
                'netbanking'           => '1',
                'recurring'            => '0',
                'gateway_merchant_id'  => 'netbanking_rbl_merchant_id',
                'gateway_merchant_id2' => 'netbanking_rbl_merchant_id2',
                'gateway_access_code'  => 'random_rbl_code',
                'created_at'           => time(),
                'updated_at'           => time()
            ]
        );
    }

    protected function createNetbankingCsbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_CSB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_CSB,
                'card'                  => '0',
                'netbanking'            => '1',
                'recurring'             => '0',
                'gateway_merchant_id'   => 'netbanking_csb_merchant_id',
                'gateway_merchant_id2'  => 'netbanking_csb_merchant_id2',
                'gateway_secure_secret' => 'test_hash_secret',
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

    protected function createEbsTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::EBS_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::EBS,
                'card'                  => '0',
                'netbanking'            => '1',
                'recurring'             => '0',
                'gateway_merchant_id'   => 'abcd',
                'gateway_secure_secret' => 'secret',
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

    protected function createAepsTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::AEPS_ICICI_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::AEPS_ICICI,
                'gateway_acquirer'      => 'icic',
                'card'                  => '0',
                'netbanking'            => '0',
                'recurring'             => '0',
                'aeps'                  => '1',
                'gateway_merchant_id'   => 'abcd',
                'gateway_secure_secret' => 'secret',
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

   protected function createHitachiGatewayMotoTerminal()
   {
       DB::table(Table::TERMINAL)->insert([
           'id'                        => Terminal\Shared::HITACHI_MOTO_TERMINAL,
           'merchant_id'               => Account::TEST_ACCOUNT,
           'gateway'                   => Gateway::HITACHI,
           'gateway_acquirer'          => 'rbl',
           'card'                      => 1,
           'type'                      => 512,
           'gateway_merchant_id'       => 'test_merchant_hitachi',
           'gateway_secure_secret'     => Crypt::encrypt('test_hitachi_secure_secret'),
           'gateway_terminal_password' => Crypt::encrypt('test_hitachi_secure_secret2'),
           'recurring'                 => 1,
           'created_at'                => time(),
           'updated_at'                => time()
       ]);
   }
    protected function createEnachRblTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::ENACH_RBL_RAZORPAY_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::ENACH_RBL,
                'gateway_acquirer'      => 'ratn',
                'card'                  => '0',
                'netbanking'            => '0',
                'recurring'             => '1',
                'emandate'              => '1',
                'gateway_access_code'   => 'RATN0TESTER',
                'gateway_merchant_id'   => 'NACH00000000001981',
                'gateway_merchant_id2'  => 'Test Merchant',
                'gateway_terminal_id'   => 'RATNTestr',
                'category'              => '6012',
                'type'                  => 6,
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

    protected function createEnachNetbankingNpciTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::ENACH_NPCI_NETBANKING_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::ENACH_NPCI_NETBANKING,
                'gateway_acquirer'      => 'yesb',
                'card'                  => '0',
                'netbanking'            => '0',
                'recurring'             => '1',
                'emandate'              => '1',
                'gateway_access_code'   => 'YESB0000001',
                'gateway_merchant_id'   => 'yesb test',
                'gateway_merchant_id2'  => 'Test Merchant',
                'gateway_terminal_id'   => 'YES BANK',
                'category'              => '6012',
                'type'                  => 6,
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

    protected function createEnstageTerminal()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::ENSTAGE_TERMINAL,
            'merchant_id'               => Account::TEST_ACCOUNT,
            'gateway'                   => Gateway::MPI_ENSTAGE,
            'gateway_acquirer'          => 'rbl',
            'card'                      => 1,
            'created_at'                => time(),
            'updated_at'                => time()
        ]);
    }

    protected function createCardlessEmiTerminal()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                          => Terminal\Shared::CARDLESS_EMI_RAZORPAY_TERMINAL,
            'merchant_id'                 => Account::TEST_ACCOUNT,
            'category'                    => 123,
            'gateway'                     => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'         => '64517b42-7b8d-4137-924a-4b6a065e7e4d',
            'gateway_merchant_id2'        => 'test merchant',
            'gateway_acquirer'            => 'zestmoney',
            'gateway_terminal_password'   => 'cmF6b3JwYXk6d3JOMzREZEZJUjJk',
            'cardless_emi'                => 1,
            'mode'                        => 1,
            'created_at'                  => time(),
            'updated_at'                  => time()
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                         => Terminal\Shared::CARDLESS_EMI_RAZORPAY_TERMINAL2,
            'merchant_id'                => Account::TEST_ACCOUNT,
            'category'                   => 123,
            'gateway'                    => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'        => '35',
            'gateway_merchant_id2'       => 'NMIMS',
            'gateway_acquirer'           => 'earlysalary',
            'gateway_terminal_password'  => 'aabbccdd',
            'cardless_emi'               => 1,
            'mode'                       => 1,
            'created_at'                 => time(),
            'updated_at'                 => time()
        ]);

        DB::table(Table::TERMINAL)->insert([
            'id'                         => Terminal\Shared::CARDLESS_EMI_FLEXMONEY_TERMINAL,
            'merchant_id'                => Account::TEST_ACCOUNT,
            'category'                   => 123,
            'gateway'                    => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'        => '35',
            'gateway_merchant_id2'       => 'NMIMS',
            'gateway_acquirer'           => 'flexmoney',
            'gateway_terminal_password'  => 'eyJpdiI6IklpRTBKZXBTYUJVVENNcms4TUVkVEE9PSIsInZhbHVlIjoiWHN4b0lZMlJBKzJvK05vdjk3NEFjMmgrb1Q5UStLODJ1UTQ5NjNndmc1UVQ3VXI4N2N0d2M0eks1SVwvUzFPK0wiLCJtYWMiOiIxNzE0ZTg5NjYwYzc2ZTE5MzViMGIyMGNkZjk1MzU3NTlkZWE5YmNjNTk2NWRkMTM3ZTA5YTgwMDI1YmQ0NzFmIn0=',
            'cardless_emi'               => 1,
            'mode'                       => 1,
            'created_at'                 => time(),
            'updated_at'                 => time()
        ]);
        DB::table(Table::TERMINAL)->insert([
            'id'                         => Terminal\Shared::CARDLESS_EMI_ZESTMONEY_TERMINAL,
            'merchant_id'                => Account::TEST_ACCOUNT,
            'category'                   => 123,
            'gateway'                    => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'        => '35',
            'gateway_merchant_id2'       => 'NMIMS',
            'gateway_acquirer'           => 'zestmoney',
            'gateway_terminal_password'  => '1234',
            'cardless_emi'               => 1,
            'mode'                       => 1,
            'created_at'                 => time(),
            'updated_at'                 => time()
        ]);
        DB::table(Table::TERMINAL)->insert([
            'id'                         => Terminal\Shared::CARDLESS_EMI_WALNUT369_TERMINAL,
            'merchant_id'                => Account::TEST_ACCOUNT,
            'category'                   => 123,
            'gateway'                    => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'        => 'gateway_merchant_id',
            'gateway_acquirer'           => 'walnut369',
            'cardless_emi'               => 1,
            'mode'                       => 3,
            'created_at'                 => time(),
            'updated_at'                 => time()
        ]);
        DB::table(Table::TERMINAL)->insert([
            'id'                         => Terminal\Shared::CARDLESS_EMI_SEZZLE_TERMINAL,
            'merchant_id'                => Account::TEST_ACCOUNT,
            'category'                   => 123,
            'gateway'                    => Gateway::CARDLESS_EMI,
            'gateway_merchant_id'        => 'gateway_merchant_id',
            'gateway_acquirer'           => 'sezzle',
            'cardless_emi'               => 1,
            'mode'                       => 2,
            'created_at'                 => time(),
            'updated_at'                 => time()
        ]);
    }

    protected function createPayLaterTerminal()
    {
        DB::table(Table::TERMINAL)->insert([
            'id'                        => Terminal\Shared::PAYLATER_EPAYLATER_TERMINAL,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paylater',
            'card'                      => 0,
            'netbanking'                => 0,
            'paylater'                  => 1,
            'gateway_merchant_id'       => 'abcd',
            'gateway_merchant_id2'      => 'ABCD',
            'gateway_acquirer'          => 'epaylater',
            'mode'                      => 1,
            'gateway_terminal_password' => Crypt::encrypt('random_secret'),
            'created_at'                => time(),
            'updated_at'                => time()
        ]);
    }

    protected function createNetbankingKvbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_KVB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_KVB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'RAZORPAY',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingSvcTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_SVC_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_SVC,
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'netbanking_svc_merchant_id',
                'created_at'            => time(),
                'updated_at'            => time()
            ]
        );
    }

    protected function createNetbankingJsbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_JSB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_JSB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'MAGMER100037',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingIobTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_IOB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_IOB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'netbanking_iob_merchant_id',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingFsbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_FSB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_FSB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'netbanking_fsb_merchant_id',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createPayuTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => 'h1t3hfU4c2A48F',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::PAYU,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_payu_mid',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createCcavenueTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => 'h1t3hfU4c2A11G',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::CCAVENUE,
                'card'                  => '1',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'test_ccavenue_mid',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'gateway_access_code'   => Crypt::encrypt('test_access_code'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingDcbTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_DCB_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_DCB,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'netbanking_dcb_merchant_id',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createNetbankingAusfTerminal(){
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                         => Terminal\Shared::NETBANKING_AUSF_TERMINAL,
                'merchant_id'                => Account::TEST_ACCOUNT,
                'gateway'                    => Gateway::NETBANKING_AUSF,
                'card'                       => '0',
                'netbanking'                 => '1',
                'gateway_merchant_id'        => 'netbanking_ausf_merchant_id',
                'gateway_terminal_password'  => Crypt::encrypt('test_terminal_password'),
                'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
                'created_at'                 => time(),
                'updated_at'                 => time(),
            ]
        );
    }

    protected function createNetbankingNsdlTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => Terminal\Shared::NETBANKING_NSDL_TERMINAL,
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::NETBANKING_NSDL,
                'card'                  => '0',
                'netbanking'            => '1',
                'gateway_merchant_id'   => 'netbanking_nsdl_merchant_id',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createCashfreeTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id' => 'h2t3hfU4c2A48G',
                'merchant_id' => Account::TEST_ACCOUNT,
                'gateway' => Gateway::CASHFREE,
                'card' => '1',
                'netbanking' => '1',
                'gateway_merchant_id' => '323395bf6400747e2f43bbd9a93323',
                'gateway_secure_secret' => Crypt::encrypt('2d2fe54f576ff428d93019f48695870abebb2327'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createZaakpayTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id' => 'h1t3hfU4c2xyz4',
                'merchant_id' => Account::TEST_ACCOUNT,
                'gateway' => Gateway::ZAAKPAY,
                'card' => '1',
                'netbanking' => '0',
                'gateway_merchant_id' => 'test_zaakpay_mid',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'gateway_secure_secret2' => Crypt::encrypt('test_secure_secret2'),
                'gateway_access_code' => 'gateway_access_code',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createPinelabsTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => 'h1t3hfU4c2A11H',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::PINELABS,
                'card'                  => '1',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_pinelabs_mid',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'gateway_access_code'   => Crypt::encrypt('test_access_code'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createIngenicoTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => 'h1t3hfU4c2A11L',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::INGENICO,
                'card'                  => '1',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_ingenico_mid',
                'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
                'gateway_access_code'   => Crypt::encrypt('test_access_code'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createBilldeskOptimizerTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id'                    => 'h1t3hfU4c2A11k',
                'merchant_id'           => Account::TEST_ACCOUNT,
                'gateway'               => Gateway::BILLDESK_OPTIMIZER,
                'card'                  => '1',
                'netbanking'            => '0',
                'gateway_merchant_id'   => 'test_billdesk_optimizer_mid',
                'gateway_secure_secret2' => Crypt::encrypt('test_secure_secret'),
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createCheckoutDotComTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                'id' => 'h1t3hfU4c2A48G',
                'merchant_id' => Account::TEST_ACCOUNT,
                'gateway' => Gateway::CHECKOUT_DOT_COM,
                'card' => '1',
                'netbanking' => '0',
                'gateway_merchant_id' => '323395bf6400747e2f43bbd9a93323',
                'created_at'            => time(),
                'updated_at'            => time(),
            ]
        );
    }

    protected function createEmerchantpayTerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            array(
                'id'                     => 'h1t3hfa4d2b48K',
                'merchant_id'            => Account::TEST_ACCOUNT,
                'gateway'                => Gateway::EMERCHANTPAY,
                'card'                   => '0',
                'netbanking'             => '0',
                'app'                    => '1',
                'enabled_apps'           => '["trustly","poli","sofort","giropay"]',
                'gateway_merchant_id'    => 'gateway_merchant_id',
                'gateway_secure_secret'  => Crypt::encrypt('gateway_secure_secret'),
                'gateway_secure_secret2' => Crypt::encrypt('gateway_secure_secret2'),
                'gateway_terminal_id'    => 'emtrustly',
                'recurring'              => 0,
                'created_at'             => time(),
                'updated_at'             => time(),
            )
        );
    }
}
