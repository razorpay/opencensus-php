<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBankTransfersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS payer_account',
            'amount',
            'updated_at',
            'payer_ifsc',
            'mode',
            'narration',
            '"*redacted*" AS payee_account',
            'utr',
            'unexpected_reason',
            'payee_ifsc',
            'time',
            'id',
            'virtual_account_id',
            'description',
            'payment_id',
            'balance_id',
            'expected',
            'merchant_id',
            'gateway',
            'notified',
            '"*redacted*" AS payer_name',
            'payer_bank_account_id',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_bank_transfers_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BANK_TRANSFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_bank_transfers_view');
    }
}
