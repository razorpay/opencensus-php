<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Payment\TerminalAnalytics\Entity as Analytics;

class CreateTerminalAnalytics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL_ANALYTICS, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments(Analytics::ID);

            $table->char(Analytics::PAYMENT_ID, Analytics::ID_LENGTH);

            $table->char(Analytics::TERMINAL_ID, Analytics::ID_LENGTH);

            $table->tinyInteger(Analytics::TERMINAL_STATUS)
                ->default(1);

            // this is recorded in milliseconds
            $table->integer(Analytics::TERMINAL_RESPONSE_TIME);
            
            $table->integer(Analytics::TERMINAL_STATUS_CODE)
                ->nullable();

            $table->text(Analytics::TERMINAL_STATUS_MSG)
                ->nullable();

            $table->tinyInteger(Analytics::PAYMENT_TYPE)
                ->default(1);

            $table->integer(Analytics::CREATED_AT);

            $table->integer(Analytics::UPDATED_AT);

            $table->foreign(Analytics::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');

            $table->foreign(Analytics::PAYMENT_ID)
                ->references(Payment\Entity::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->index(Analytics::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL_ANALYTICS, function($table)
        {
            $table->dropForeign(
                Table::TERMINAL_ANALYTICS. '_' .Analytics::TERMINAL_ID.'_foreign');

            $table->dropForeign(
                Table::TERMINAL_ANALYTICS. '_' .Analytics::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::TERMINAL_ANALYTICS);
    }
}
