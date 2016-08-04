<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Terminal\Action\Entity as Action;

class CreateTerminalActionLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL_ACTION, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments(Action::ID);

            $table->char(Action::TERMINAL_ID, Action::ID_LENGTH);

            $table->enum(Action::ACTION, Action::ACTION_STATES);

            $table->integer(Action::CREATED_AT);

            $table->integer(Action::UPDATED_AT);

            $table->foreign(Action::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');

            $table->index(Action::CREATED_AT);

            $table->index(Action::ACTION);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL_ACTION, function($table)
        {
            $table->dropForeign(
                TABLE::TERMINAL_ACTION.'_'.Action::TERMINAL_ID.'_foreign');

        });

        Schema::drop(Table::TERMINAL_ACTION);
    }
}
