<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPromotionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'name',
            'pricing_plan_id',
            'activated_at',
            'reference5',
            'credit_amount',
            'partner_id',
            'deactivated_at',
            'credit_type',
            'created_at',
            'status',
            'schedule_id',
            'updated_at',
            'deactivated_by',
            'iterations',
            'product',
            'reference1',
            'credits_expire',
            'event_id',
            'reference2',
            'purpose',
            'start_at',
            'reference3',
            'id',
            'creator_name',
            'end_at',
            'reference4'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_promotions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PROMOTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_promotions_view');
    }
}
