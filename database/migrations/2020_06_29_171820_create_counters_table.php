<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Balance;
use RZP\Models\Counter\Entity as Counter;

class CreateCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::COUNTER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Counter::ID, Counter::ID_LENGTH)
                  ->primary();

            $table->char(Counter::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->unsignedInteger(Counter::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT);

            $table->integer(Counter::FREE_PAYOUTS_CONSUMED)
                  ->default(0);

            $table->string(Counter::ACCOUNT_TYPE, 255)
                  ->nullable();

            $table->integer(Counter::CREATED_AT);

            $table->integer(Counter::UPDATED_AT);

            $table->foreign(Counter::BALANCE_ID)
                  ->references(Balance\Entity::ID)
                  ->on(Table::BALANCE)
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
        Schema::table(Table::COUNTER, function($table)
        {
            $table->dropForeign(Table::COUNTER . '_' . COUNTER::BALANCE_ID . '_foreign');
        });

        Schema::drop(Table::COUNTER);
    }
}
