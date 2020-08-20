<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCustomersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'notes',
            'id',
            'active',
            'global_customer_id',
            'created_at',
            'merchant_id',
            'updated_at',
            '"*redacted*" AS name',
            'deleted_at',
            '"*redacted*" AS contact',
            '"*redacted*" AS email',
            '"*redacted*" AS gstin'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_customers_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CUSTOMER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_customers_view');
    }
}
