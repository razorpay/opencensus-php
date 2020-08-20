<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedTaxGroupsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'updated_at',
            'deleted_at',
            'id',
            'merchant_id',
            'name',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_tax_groups_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TAX_GROUP;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_tax_groups_view');
    }
}
