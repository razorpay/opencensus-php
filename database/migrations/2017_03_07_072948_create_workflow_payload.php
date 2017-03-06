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

            $table->char(Payload::ACTION_ID, Payload::ID_LENGTH);

            $table->boolean(Payload::ENCRYPTED)
                  ->nullable();

            $table->text(Payload::REQUEST);

            $table->foreign(Payload::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->integer(Payload::CREATED_AT);
            $table->integer(Payload::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ACTION_PAYLOAD, function($table)
        {
            $table->dropForeign(Table::ACTION_PAYLOAD . '_' . Payload::ACTION_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_PAYLOAD);
    }
}
