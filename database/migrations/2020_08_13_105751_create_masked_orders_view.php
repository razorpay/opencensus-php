<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedOrdersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'attempts',
            'first_payment_min_amount',
            'reference2',
            'reference10',
            'app_offer',
            'status',
            'amount_paid',
            'reference3',
            'created_at',
            'receipt',
            '"*redacted*" AS notes',
            'reference4',
            'updated_at',
            'payment_capture',
            'method',
            'reference5',
            'checkout_config_id',
            'id',
            'customer_id',
            'bank',
            'reference6',
            'late_auth_config_id',
            'merchant_id',
            'offer_id',
            '"*redacted*" AS account_number',
            'reference7',
            'provider_context',
            'amount',
            'discount',
            'authorized',
            'reference8',
            'product_id',
            'currency',
            'partial_payment',
            'force_offer',
            '"*redacted*" AS payer_name',
            'product_type'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_orders_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ORDER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_orders_view');
    }
}
