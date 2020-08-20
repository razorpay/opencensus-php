<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedNetbankingView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'account_type',
            'merchant_code',
            'refund_id',
            '"*redacted*" AS credit_account_number',
            'customer_id',
            'caps_payment_id',
            'id',
            'status',
            '"*redacted*" AS customer_name',
            'si_token',
            'payment_id',
            'action',
            'bank_payment_id',
            'si_status',
            'int_payment_id',
            'received',
            'error_message',
            'si_message',
            '"*redacted*" AS account_number',
            'amount',
            'reference1',
            'created_at',
            'account_branch_code',
            'bank',
            'verification_id',
            'updated_at',
            'account_subtype',
            'client_code',
            'date'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_netbanking_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::NETBANKING;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_netbanking_view');
    }
}
