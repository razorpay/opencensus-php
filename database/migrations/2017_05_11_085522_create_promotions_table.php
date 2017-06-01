<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Schedule;
use RZP\Models\Promotion\Entity as Promotion;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PROMOTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Promotion::ID, Promotion::ID_LENGTH)
                  ->primary();

            $table->string(Promotion::NAME, Promotion::NAME_LENGTH);

            $table->integer(Promotion::AMOUNT)
                  ->unsigned();

            $table->string(Promotion::CREDIT_TYPE, Promotion::CREDIT_TYPE_LENGTH);

            $table->char(Promotion::SCHEDULE_ID, Promotion::ID_LENGTH)
                  ->nullable();

            $table->integer(Promotion::ITERATIONS)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Promotion::CREDITS_EXPIRE)
                  ->default(0);

            $table->integer(Promotion::CREATED_AT);

            $table->integer(Promotion::UPDATED_AT);

            $table->foreign(Promotion::SCHEDULE_ID)
                  ->references(Schedule\Entity::ID)
                  ->on(Table::SCHEDULE)
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
        Schema::table(Table::PROMOTION, function($table)
        {
            $table->dropForeign(Table::PROMOTION.'_'.Promotion::SCHEDULE_ID.'_foreign');
        });

        Schema::drop(Table::PROMOTION);
    }
}
