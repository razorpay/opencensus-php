<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Workflow\Action\Payload\Entity as Payload;

class CreateWorkflowPayload extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACTION_PAYLOAD, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payload::ID, Payload::ID_LENGTH)
                  ->primary();

            $table->boolean(Payload::ENCRYPTED)
                  ->nullable();

            $table->text(Payload::REQUEST);

            $table->integer(Payload::CREATED_AT);
            $table->integer(Payload::UPDATED_AT);
        });

        Schema::table(Table::WORKFLOW_ACTION, function(Blueprint $table)
        {
            $table->foreign(Action::PAYLOAD_ID)
                  ->references(Payload::ID)
                  ->on(Table::ACTION_PAYLOAD)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::WORKFLOW_ACTION, function($table)
        {
            $table->dropForeign(
                Table::WORKFLOW_ACTION . '_' . Action::PAYLOAD_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_PAYLOAD);
    }
}
