<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCardlessEmiView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'provider',
            'error_description',
            'amount',
            '"*redacted*" AS contact',
            'currency',
            '"*redacted*" AS email',
            'id',
            'gateway_reference_id',
            'created_at',
            'action',
            'gateway_plan_id',
            'updated_at',
            'payment_id',
            'status',
            'refund_id',
            'received',
            'gateway',
            'error_code'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_cardless_emi_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CARDLESS_EMI;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_cardless_emi_view');
    }
}
