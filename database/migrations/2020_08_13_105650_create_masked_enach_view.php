<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedEnachView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'authentication_gateway',
            'gateway_reference_id',
            'updated_at',
            'action',
            'gateway_reference_id2',
            'acquirer',
            'acknowledge_status',
            'bank',
            'registration_status',
            'amount',
            'registration_date',
            'id',
            'status',
            'error_message',
            'payment_id',
            '"*redacted*" AS signed_xml',
            'error_code',
            'refund_id',
            'umrn',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_enach_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ENACH;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_enach_view');
    }
}
