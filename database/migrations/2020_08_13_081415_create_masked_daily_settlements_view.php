<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedDailySettlementsView extends Migration
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
            'type',
            'api_fee',
            'initiated_at',
            'date',
            'gateway_fee',
            'reconciled_at',
            'channel',
            'total_count',
            'returned_at',
            'amount',
            'processed_count',
            'processed_amount',
            'transaction_count',
            'fees',
            'urls',
            'txt_file_id',
            'tax',
            'excel_file_id',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_daily_settlements_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BATCH_FUND_TRANSFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_daily_settlements_view');
    }
}
