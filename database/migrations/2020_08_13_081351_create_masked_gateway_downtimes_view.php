<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedGatewayDowntimesView extends Migration
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
            'gateway',
            'issuer',
            'acquirer',
            'reason_code',
            'source',
            'terminal_id',
            'card_type',
            'network',
            'method',
            'psp',
            'vpa_handle',
            'comment',
            'begin',
            'end',
            'scheduled',
            'partial',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_gateway_downtimes_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::GATEWAY_DOWNTIME;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_gateway_downtimes_view');
    }
}
