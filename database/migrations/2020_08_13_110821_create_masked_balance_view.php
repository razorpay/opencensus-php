<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBalanceView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'balance',
            'created_at',
            'on_hold',
            'updated_at',
            'credits',
            'locked_balance',
            'id',
            'fee_credits',
            'merchant_id',
            'refund_credits',
            'type',
            '"*redacted*" AS account_number',
            'currency',
            'account_type',
            'name',
            'channel'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_balance_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BALANCE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_balance_view');
    }
}
