<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPayoutDowntimesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'channel',
            'enabled_email_status',
            'mode',
            'disabled_email_status',
            'start_time',
            'created_by',
            'end_time',
            'created_at',
            'downtime_message',
            'updated_at',
            'uptime_message',
            'id',
            'enabled_email_option',
            'status',
            'disabled_email_option'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payout_downtimes_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYOUT_DOWNTIMES;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payout_downtimes_view');
    }
}
