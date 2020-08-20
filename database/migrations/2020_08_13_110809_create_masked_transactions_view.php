<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedTransactionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS fee',
            'gateway_amount',
            'fee_bearer',
            'reconciled_at',
            'reference8',
            'mdr',
            '"*redacted*" AS gateway_fee',
            'fee_model',
            'reconciled_type',
            'reference9',
            'tax',
            'gateway_service_tax',
            'credit_type',
            'balance_id',
            'posted_at',
            'id',
            'pricing_rule_id',
            'api_fee',
            'on_hold',
            'reference3',
            'created_at',
            'entity_id',
            'debit',
            'gratis',
            'settled',
            'reference4',
            'updated_at',
            'type',
            '"*redacted*" AS credit',
            '"*redacted*" AS fee_credits',
            'settled_at',
            'balance_updated',
            'merchant_id',
            'currency',
            'escrow_balance',
            'gateway_settled_at',
            'reference6',
            'amount',
            'balance',
            'channel',
            'settlement_id',
            'reference7'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_transactions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TRANSACTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_transactions_view');
    }
}
