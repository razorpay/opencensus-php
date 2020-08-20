<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedTokensView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'method',
            '"*redacted*" AS aadhaar_number',
            'start_time',
            'created_at',
            'card_id',
            '"*redacted*" AS aadhaar_vid',
            'confirmed_at',
            'updated_at',
            'bank',
            '"*redacted*" AS gateway_token',
            'rejected_at',
            'expired_at',
            'id',
            'wallet',
            '"*redacted*" AS gateway_token2',
            'initiated_at',
            'deleted_at',
            'customer_id',
            '"*redacted*" AS account_number',
            'auth_type',
            'acknowledged_at',
            'vpa_id',
            'merchant_id',
            'account_type',
            'recurring',
            'max_amount',
            'debit_type',
            'terminal_id',
            '"*redacted*" AS beneficiary_name',
            'recurring_status',
            'used_count',
            'frequency',
            '"*redacted*" AS token',
            'ifsc',
            'recurring_failure_reason',
            'used_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_tokens_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TOKEN;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_tokens_view');
    }
}
