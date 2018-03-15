<?php

use Illuminate\Database\Seeder;

use RZP\Models\Tax\Gst;
use RZP\Constants\Table;
use RZP\Models\Merchant\Account;

class TaxGroupAndTaxSeeder extends Seeder
{
    public function run()
    {
        Eloquent::unguard();

        DB::transaction(function() {
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
                    'id'          => Gst::IGST_0,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::IGST_500,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::IGST_1200,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::IGST_1800,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'IGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::IGST_2800,
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
                    'id'          => Gst::CGST_0,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_250,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_500,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_600,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_900,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_1200,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_1400,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_1800,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'CGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::CGST_2800,
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
                    'id'          => Gst::SGST_0,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_250,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_500,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_600,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_900,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_1200,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_1400,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_1800,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'SGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::SGST_2800,
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
                    'id'          => Gst::UTGST_0,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 0%',
                    'rate_type'   => 'flat',
                    'rate'        => '0',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_250,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 2.5%',
                    'rate_type'   => 'flat',
                    'rate'        => '250',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_500,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 5%',
                    'rate_type'   => 'flat',
                    'rate'        => '500',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_600,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 6%',
                    'rate_type'   => 'flat',
                    'rate'        => '600',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_900,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 9%',
                    'rate_type'   => 'flat',
                    'rate'        => '900',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_1200,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 12%',
                    'rate_type'   => 'flat',
                    'rate'        => '1200',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_1400,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 14%',
                    'rate_type'   => 'flat',
                    'rate'        => '1400',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_1800,
                    'merchant_id' => Account::SHARED_ACCOUNT,
                    'name'        => 'UTGST 18%',
                    'rate_type'   => 'flat',
                    'rate'        => '1800',
                    'created_at'  => time(),
                    'updated_at'  => time(),
                    'deleted_at'  => null,
                ],
                [
                    'id'          => Gst::UTGST_2800,
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
