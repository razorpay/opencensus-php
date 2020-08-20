<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedUpiMandatesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'token_id',
            'start_time',
            'confirmed_at',
            'order_id',
            'end_time',
            'created_at',
            'receipt',
            'umn',
            'updated_at',
            'status',
            'rrn',
            'max_amount',
            'npci_txn_id',
            'id',
            'frequency',
            'gateway_data',
            'merchant_id',
            'recurring_type',
            'used_count',
            'customer_id',
            'recurring_value',
            'late_confirmed'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_upi_mandates_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::UPI_MANDATE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_upi_mandates_view');
    }
}
