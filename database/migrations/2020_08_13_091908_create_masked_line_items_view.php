<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedLineItemsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'ref_type',
            'net_amount',
            'merchant_id',
            'entity_id',
            'currency',
            'quantity',
            'entity_type',
            'type',
            'created_at',
            'name',
            'tax_inclusive',
            'updated_at',
            'description',
            'hsn_code',
            'deleted_at',
            'id',
            'amount',
            'sac_code',
            'item_id',
            'gross_amount',
            'tax_rate',
            'ref_id',
            'tax_amount',
            'unit'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_line_items_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::LINE_ITEM;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_line_items_view');
    }
}
