<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBatchesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'gateway',
            'attempts',
            'updated_at',
            'failure_reason',
            'amount',
            'processing',
            'processed_amount',
            'id',
            'status',
            'comment',
            'merchant_id',
            'total_count',
            'creator_id',
            'type',
            'processed_count',
            'creator_type',
            'sub_type',
            'success_count',
            'processed_at',
            'name',
            'failure_count',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_batches_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BATCH;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_batches_view');
    }
}
