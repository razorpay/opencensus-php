<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedFundAccountValidationsView extends Migration
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
            'amount',
            'error_description',
            'status',
            'currency',
            'notes',
            'fts_transfer_id',
            'batch_fund_transfer_id',
            'created_at',
            'account_status',
            'retry_at',
            'updated_at',
            'id',
            '"*redacted*" AS registered_name',
            'attempts',
            'receipt',
            'utr',
            'balance_id',
            'fund_account_id',
            'fees',
            'error_code',
            'fund_account_type',
            'tax',
            'internal_error_code'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_fund_account_validations_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::FUND_ACCOUNT_VALIDATION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_fund_account_validations_view');
    }
}
