<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedHitachiView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'action',
            'pStatus',
            'received',
            'masked_card_number',
            'amount',
            'card_network',
            'id',
            'currency',
            'merchant_reference',
            'payment_id',
            'pRequestId',
            'pAuthID',
            'refund_id',
            'pRespCode',
            'created_at',
            'acquirer',
            'pAuthStatus',
            'updated_at',
            'authentication_gateway',
            'pRRN'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_hitachi_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::HITACHI;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_hitachi_view');
    }
}
