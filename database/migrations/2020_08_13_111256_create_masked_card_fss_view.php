<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCardFssView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'currency',
            'auth_res_code',
            'id',
            'status',
            'created_at',
            'payment_id',
            'gateway_payment_id',
            'updated_at',
            'action',
            'tranid',
            'acquirer',
            'ref',
            'received',
            'auth',
            'refund_id',
            'postdate',
            'amount',
            'error_message'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_card_fss_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CARD_FSS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_card_fss_view');
    }
}
