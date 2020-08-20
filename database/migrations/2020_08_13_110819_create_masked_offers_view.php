<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedOffersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'merchant_id',
            'name',
            'payment_method',
            'payment_method_type',
            'iins',
            'payment_network',
            'issuer',
            'international',
            'active',
            'block',
            'checkout_display',
            'type',
            'percent_rate',
            'min_amount',
            'processing_time',
            'max_offer_usage',
            'max_cashback',
            'starts_at',
            'current_offer_usage',
            'flat_cashback',
            'ends_at',
            'default_offer',
            'emi_subvention',
            'display_text',
            'max_order_amount',
            'emi_durations',
            'error_message',
            'max_payment_count',
            'terms',
            'linked_offer_ids',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_offers_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::OFFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_offers_view');
    }
}
