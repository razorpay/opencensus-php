<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use Illuminate\Database\Seeder;
use RZP\Models\Merchant\Balance\AccountType;

class BusinessBankingSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $mode = DB::connection()->getName();

        DB::transaction(function() use ($mode)
        {
            $this->seedContacts();
            $this->seedBankingBalance($mode);
            $this->seedBankingVA($mode);
            $this->seedBankingVATerminal($mode);
            $this->seedPayoutFeature();
        });
    }

    private function seedContacts()
    {
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
            ]);
    }

    private function seedBankingBalance($mode)
    {
        DB::table(Table::BALANCE)->insert(
            [
                [
                    'id'             => 'xbalance000000',
                    'merchant_id'    => '10000000000000',
                    'type'           => 'banking',
                    'balance'        => 0,
                    'currency'       => 'INR',
                    // The account numbers are different in case of live and test mode
                    // check terminal entity below
                    'account_number' => ($mode === Mode::LIVE) ? '2224440041626905' : '2323230041626905',
                    'account_type'   => AccountType::SHARED,
                    'channel'        => null,
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ],
            ]);
    }

    private function seedBankingVA($mode)
    {
        DB::table(Table::BANK_ACCOUNT)->insert(
            [
                [
                    'id'                   => 'xba00000000000',
                    'merchant_id'          => '10000000000000',
                    'entity_id'            => 'xva00000000000',
                    'type'                 => 'virtual_account',
                    // The account numbers and ifsc are different in case of live and test mode
                    // check terminal entity below
                    'ifsc_code'            => ($mode === Mode::LIVE) ? 'YESB0000000' : 'RAZRB000000',
                    'account_number'       => ($mode === Mode::LIVE) ? '2224440041626905' : '2323230041626905',
                    'beneficiary_name'     => 'random_name',
                    'beneficiary_address1' => 'address1',
                    'beneficiary_address2' => 'address2',
                    'beneficiary_address3' => 'address3',
                    'beneficiary_address4' => 'address4',
                    'beneficiary_city'     => 'new delhi',
                    'beneficiary_state'    => 'DE',
                    'beneficiary_country'  => 'IN',
                    'beneficiary_email'    => 'random@email.com',
                    'beneficiary_mobile'   => '9988776655',
                    'beneficiary_pin'      => '100000',
                    'created_at'           => time(),
                    'updated_at'           => time(),
                    'deleted_at'           => null,
                ],
            ]);

        DB::table(Table::VIRTUAL_ACCOUNT)->insert(
            [
                [
                    'id'              => 'xva00000000000',
                    'merchant_id'     => '10000000000000',
                    'status'          => 'active',
                    'name'            => 'Test Merchant',
                    'balance_id'      => 'xbalance000000',
                    'amount_received' => 0,
                    'amount_paid'     => 0,
                    'amount_reversed' => 0,
                    'bank_account_id' => 'xba00000000000',
                    'notes'           => '{}',
                    'created_at'      => time(),
                    'updated_at'      => time(),
                    'deleted_at'      => null,
                ],
            ]);

        DB::table(Table::BANKING_ACCOUNT)->insert(
            [
                [
                    'id'                   => 'xbacc000000000',
                    'merchant_id'          => '10000000000000',
                    // The account numbers and ifsc are different in case of live and test mode
                    // check terminal entity below
                    'account_ifsc'         => ($mode === Mode::LIVE) ? 'YESB0000000' : 'RAZRB000000',
                    'account_number'       => ($mode === Mode::LIVE) ? '2224440041626905' : '2323230041626905',
                    'status'               => 'activated',
                    'channel'              => 'yesbank',
                    'account_type'         => 'nodal',
                    'balance_id'           => 'xbalance000000',
                    'beneficiary_name'     => 'random_name',
                    'account_currency'     => 'INR',
                    'beneficiary_address1' => 'address1',
                    'beneficiary_address2' => 'address2',
                    'beneficiary_address3' => 'address3',
                    'beneficiary_city'     => 'new delhi',
                    'beneficiary_state'    => 'DE',
                    'beneficiary_country'  => 'IN',
                    'beneficiary_email'    => 'random@email.com',
                    'beneficiary_mobile'   => '9988776655',
                    'beneficiary_pin'      => '100000',
                    'created_at'           => time(),
                    'updated_at'           => time(),
                ],
            ]);
    }

    private function seedBankingVATerminal($mode)
    {
        DB::table(Table::TERMINAL)->insert(
            [
                [
                    'id'                   => 'xterminal00000',
                    'merchant_id'          => '100000Razorpay',
                    // IN test mode we select the terminal as bt_dashboard and in live mode as bt_icici
                    'gateway'              => ($mode === Mode::LIVE) ? 'bt_icici' : 'bt_dashboard',
                    // For live and test mode the account numbers should be different due to different terminals
                    'gateway_merchant_id'  => ($mode === Mode::LIVE) ? '3434' : '232323',
                    'gateway_merchant_id2' => '00',
                    'card'                 => 0,
                    'recurring'            => 0,
                    'gateway_acquirer'     => null,
                    'type'                 => 20481,
                    'bank_transfer'        => '1',
                    'created_at'           => time(),
                    'updated_at'           => time(),
                    'deleted_at'           => null,
                ]
            ]);
    }

    private function seedPayoutFeature()
    {
        DB::table(Table::FEATURE)->insert(
            [
                [
                    'id'            => 'feature_x0x0x0',
                    'name'          => 'payout',
                    'entity_id'     => '10000000000000',
                    'entity_type'   => 'merchant',
                    'created_at'    => time(),
                    'updated_at'    => time()
                ]
        ]);
    }
}
