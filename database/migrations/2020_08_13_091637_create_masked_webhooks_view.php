<?php


use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWebhooksView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'active',
            'last_successful_at',
            'disable_on_failure',
            'created_at',
            'url',
            'updated_at',
            '"*redacted*" AS secret',
            'events2',
            'events',
            'deleted_at',
            'entity_type',
            'entity_id',
            'id',
            'failure_count',
            'merchant_id'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_webhooks_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WEBHOOK;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_webhooks_view');
    }
}
