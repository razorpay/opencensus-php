<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedItemsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'description',
            'tax_id',
            'amount',
            'tax_group_id',
            'currency',
            'type',
            'unit',
            'created_at',
            'id',
            'tax_inclusive',
            'updated_at',
            'active',
            'hsn_code',
            'deleted_at',
            'merchant_id',
            'sac_code',
            'name',
            'tax_rate'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_items_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ITEM;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_items_view');
    }
}
