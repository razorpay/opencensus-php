<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedTransfersView extends Migration
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
            'to_id',
            'tax',
            'to_type',
            'on_hold',
            'processed_at',
            'amount',
            'on_hold_until',
            'attempts',
            'currency',
            'merchant_id',
            'amount_reversed',
            'transaction_id',
            'source_id',
            'notes',
            'recipient_settlement_id',
            'source_type',
            'fees',
            'message',
            'status',
            'origin',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_transfers_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TRANSFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_transfers_view');
    }
}
