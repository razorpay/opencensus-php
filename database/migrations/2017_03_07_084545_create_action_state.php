<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Workflow\Action\State\Entity as State;

class CreateActionState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACTION_STATE, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(State::ID, State::ID_LENGTH)
                  ->primary();

            $table->char(State::ACTION_ID, State::ID_LENGTH);

            $table->char(State::ADMIN_ID, State::ID_LENGTH)
                  ->nullable();

            $table->char(State::NAME, 255);

            $table->foreign(State::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(State::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->integer(State::CREATED_AT);
            $table->integer(State::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ACTION_STATE, function($table)
        {
            $table->dropForeign(Table::ACTION_STATE . '_' . State::ACTION_ID . '_foreign');

            $table->dropForeign(Table::ACTION_STATE . '_' . State::ADMIN_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_STATE);
    }
}
