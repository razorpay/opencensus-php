<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedAddressesView extends Migration
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
            'entity_type',
            '`primary`',
            '"*redacted*" AS line1',
            '"*redacted*" AS line2',
            '"*redacted*" AS city',
            '"*redacted*" AS zipcode',
            '"*redacted*" AS state',
            '"*redacted*" AS country',
            'entity_id',
            'type',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_addresses_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ADDRESS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_addresses_view');
    }
}
