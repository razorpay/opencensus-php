<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBankingAccountStatementView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'merchant_id',
            'category',
            'updated_at',
            '"*redacted*" AS account_number',
            'bank_serial_number',
            'bank_transaction_id',
            'bank_instrument_id',
            'id',
            'type',
            'balance',
            'transaction_id',
            'utr',
            'balance_currency',
            'entity_id',
            'amount',
            'transaction_date',
            'entity_type',
            'currency',
            'posted_date',
            'channel',
            'description',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_banking_account_statement_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BANKING_ACCOUNT_STATEMENT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_banking_account_statement_view');
    }
}
