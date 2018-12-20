<?php

use RZP\Constants\Table;
use Illuminate\Database\Seeder;

class BusinessBankingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function()
        {
            $this->seedContacts();
            $this->seedBankingBalance();
            $this->seedBankingVA();
            $this->seedBankingVATerminal();
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

    private function seedBankingBalance()
    {
        DB::table(Table::BALANCE)->insert(
            [
                [
                    'id'             => 'xbalance000000',
                    'merchant_id'    => '10000000000000',
                    'type'           => 'banking',
                    'balance'        => 0,
                    'currency'       => 'INR',
                    'account_number' => '2224440041626905',
                    'created_at'     => time(),
                    'updated_at'     => time(),
                ],
            ]);
    }

    private function seedBankingVA()
    {
        DB::table(Table::BANK_ACCOUNT)->insert(
            [
                [
                    'id'                   => 'xba00000000000',
                    'merchant_id'          => '10000000000000',
                    'entity_id'            => 'xva00000000000',
                    'type'                 => 'virtual_account',
                    'ifsc_code'            => 'RAZRB000000',
                    'account_number'       => '2224440041626905',
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
                    'bank_account_id' => 'xba00000000000',
                    'notes'           => '{}',
                    'created_at'      => time(),
                    'updated_at'      => time(),
                    'deleted_at'      => null,
                ],
            ]);
    }

    private function seedBankingVATerminal()
    {
        DB::table(Table::TERMINAL)->insert(
            [
                [
                    'id'                   => 'xterminal00000',
                    'merchant_id'          => '10000000000000',
                    'gateway'              => 'bt_yesbank',
                    'gateway_merchant_id'  => '222444',
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
}
