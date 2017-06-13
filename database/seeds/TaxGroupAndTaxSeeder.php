<?php

use Illuminate\Database\Seeder;

use RZP\Constants\Table;

class TaxGroupAndTaxSeeder extends Seeder
{
    public function run()
    {
        Eloquent::unguard();

        DB::transaction(function()
        {
            $this->seedTaxGroups();
            $this->seedTaxes();
            $this->seedTaxGroupTaxMap();
        });
    }

    private function seedTaxGroups()
    {
        DB::table(Table::TAX_GROUP)->insert(
            [
                [
                    'id'          => '00000000000001',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Tax Group #1',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '00000000000002',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Tax Group #2',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
            ]);
    }

    private function seedTaxes()
    {
        DB::table(Table::TAX)->insert(
            [
                [
                    'id'          => '00000000000001',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Tax #1',
                    'rate_type'   => 'percentage',
                    'rate'        => '1000',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '00000000000002',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Tax #2',
                    'rate_type'   => 'percentage',
                    'rate'        => '2000',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '00000000000003',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Tax #3',
                    'rate_type'   => 'percentage',
                    'rate'        => '1500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '00000000000004',
                    'merchant_id' => '10000000000000',
                    'name'        => 'Flat Tax #4',
                    'rate_type'   => 'flat',
                    'rate'        => '100',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
            ]);
    }

    private function seedTaxGroupTaxMap()
    {
        DB::table(Table::TAX_GROUP_TAX_MAP)->insert(
            [
                [
                    'tax_group_id' => '00000000000001',
                    'tax_id'       => '00000000000001',
                    'created_at'   => time(),
                    'updated_at'   => time(),
                ],
                [
                    'tax_group_id' => '00000000000001',
                    'tax_id'       => '00000000000002',
                    'created_at'   => time(),
                    'updated_at'   => time(),
                ],
                [
                    'tax_group_id' => '00000000000002',
                    'tax_id'       => '00000000000001',
                    'created_at'   => time(),
                    'updated_at'   => time(),
                ],
                [
                    'tax_group_id' => '00000000000002',
                    'tax_id'       => '00000000000004',
                    'created_at'   => time(),
                    'updated_at'   => time(),
                ],
            ]);
    }
}
