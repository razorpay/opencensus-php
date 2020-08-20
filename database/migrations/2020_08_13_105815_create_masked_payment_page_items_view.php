<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaymentPageItemsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'plan_id',
            'min_amount',
            'mandatory',
            'max_amount',
            'image_url',
            'created_at',
            'stock',
            'updated_at',
            'id',
            'quantity_sold',
            'deleted_at',
            'merchant_id',
            'total_amount_paid',
            'product_config',
            'payment_link_id',
            'min_purchase',
            'item_id',
            'max_purchase'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payment_page_items_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYMENT_PAGE_ITEM;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payment_page_items_view');
    }
}
