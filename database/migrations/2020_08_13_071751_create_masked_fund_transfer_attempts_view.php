<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedFundTransferAttemptsView extends Migration
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
            'channel',
            'utr',
            'created_at',
            'merchant_id',
            'version',
            'narration',
            'updated_at',
            'purpose',
            'bank_status_code',
            'remarks',
            'gateway_ref_no',
            'source_type',
            'bank_response_code',
            'failure_reason',
            'source_id',
            'mode',
            'date_time',
            'bank_account_id',
            'status',
            'cms_ref_no',
            'vpa_id',
            'is_fts',
            'batch_fund_transfer_id',
            'card_id',
            'fts_transfer_id',
            'initiate_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_fund_transfer_attempts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::FUND_TRANSFER_ATTEMPT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_fund_transfer_attempts_view');
    }
}
