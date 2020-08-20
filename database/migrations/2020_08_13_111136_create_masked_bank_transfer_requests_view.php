<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBankTransferRequestsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS payee_account',
            'time',
            'id',
            'payee_ifsc',
            'request_payload',
            'gateway',
            '"*redacted*" AS payer_name',
            'created_at',
            'is_created',
            'payer_account',
            'updated_at',
            'error_message',
            'payer_ifsc',
            'utr',
            'amount',
            'mode',
            'description',
            '"*redacted*" AS payee_name',
            'narration'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_bank_transfer_requests_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BANK_TRANSFER_REQUEST;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_bank_transfer_requests_view');
    }
}
