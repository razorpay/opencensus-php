<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Webhook\Entity as Webhook;

class CreateQuboleWebhooksView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Webhook::ID,
            Webhook::MERCHANT_ID,
            Webhook::ACTIVE,
            Webhook::DISABLE_ON_FAILURE,
            Webhook::URL,
            Webhook::EVENTS,
            Webhook::ENTITY_TYPE,
            Webhook::ENTITY_ID,
            Webhook::FAILURE_COUNT,
            Webhook::LAST_SUCCESSFUL_AT,
            Webhook::CREATED_AT,
            Webhook::UPDATED_AT

        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_webhooks_view AS
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
        DB::statement('DROP VIEW IF EXISTS qubole_webhooks_view');
    }
}
