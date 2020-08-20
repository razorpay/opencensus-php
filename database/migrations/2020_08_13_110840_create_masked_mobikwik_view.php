<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedMobikwikView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS email',
            'statuscode',
            'amount',
            'statusmessage',
            '"*redacted*" AS cell',
            'refid',
            'id',
            'orderid',
            'ispartial',
            'payment_id',
            'txid',
            'refund_id',
            'action',
            'mid',
            'created_at',
            'method',
            'merchantname',
            'updated_at',
            'received',
            'showmobile'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_mobikwik_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MOBIKWIK;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_mobikwik_view');
    }
}
