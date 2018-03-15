<?php

use Illuminate\Database\Seeder;

use RZP\Constants\Table;
use RZP\Models\Merchant\Account;

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

                //
                // GST Taxes
                //

                // IGST
                [
                    'id'          => '9nDpYboKAK9j7t',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYciCWeNBzE',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYdbYNqD4Rw',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYf1tTUs2Vh',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYfqgnYW5Dx',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 28%',
                    'rate_type'   => 'flat',
                    'rate'        => '2800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],

                // CGST
                [
                    'id'          => '9nDpYglSpU58lc',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYhZ0d60X7V',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYiArP6j0qT',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYivRHUQQV8',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYjuyZsOlMK',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYkng64GyTa',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYlTs7cWM80',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYmPK2K2mVi',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYnFEoqJQ5v',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 28%',
                    'rate_type'   => 'flat',
                    'rate'        => '2800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],

                // SGST
                [
                    'id'          => '9nDpYnvgiGXrZh',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYoeYBsXRvC',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYpMRZgJEgU',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYpuN72gdfY',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYqgYcqpr8q',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYrIMXQTtPd',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYs1yK0pndD',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYsoU7subph',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYtb0S0JhMP',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 28%',
                    'rate_type'   => 'flat',
                    'rate'        => '2800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],

                // UTGST
                [
                    'id'          => '9nDpYuFVNQcVaU',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYv53mqSsip',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYvgwu0p8WP',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYwRScK0Mz2',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYxMkO0LLhz',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYyC50acDzW',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYz26oaOHgI',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpYznDzU7NKP',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => '9nDpZ0hEw4vZky',
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 28%',
                    'rate_type'   => 'flat',
                    'rate'        => '2800',
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
