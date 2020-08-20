<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedMerchantBanksView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'merchant_id',
            'netbanking',
            'disabled_banks',
            'paytm',
            'freecharge',
            'upi',
            'emi',
            'mobikwik',
            'jiomoney',
            'emandate',
            'cardless_emi',
            'payzapp',
            'sbibuddy',
            'apps',
            'nach',
            'paylater',
            'payumoney',
            'mpesa',
            'bank_transfer',
            'openwallet',
            'airtelmoney',
            'card',
            'aeps',
            'debit_card',
            'olamoney',
            'amazonpay',
            'card_networks',
            'amex',
            'prepaid_card',
            'phonepe',
            'card_subtype',
            'banks',
            'credit_card',
            'paypal',
            'created_at',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_merchant_banks_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::METHODS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_merchant_banks_view');
    }
}
