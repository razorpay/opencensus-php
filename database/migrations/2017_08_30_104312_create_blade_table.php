<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBladeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BLADE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Blade::ID);

            $table->char(Blade::ACTION, 10)
                  ->nullable();

            $table->string(Blade::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->integer(Blade::RECEIVED)
                  ->default(0);

            $table->char(Blade::REFUND_ID, UniqueIdEntity::ID_LENGTH)
                  ->nullable();

            $table->integer(Blade::AMOUNT);

            $table->string(Blade::CURRENCY, 3)
                  ->nullable();

            $table->char(Blade::PARES_STATUS, 2)
                  ->nullable();

            $table->char(Blade::STATUS, 20);

            $table->char(Blade::XID, 40)
                  ->nullable();

            $table->char(Blade::CAVV, 40)
                  ->nullable();

            $table->integer(Blade::CREATED_AT);

            $table->integer(Blade::UPDATED_AT);

            $table->char(Blade::VERES_ENROLLED, 2)
                  ->nullable();

            $table->char(Blade::ECI, 2)
                  ->nullable();

            $table->foreign(Blade::PAYMENT_ID)
                  ->references(Blade::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Blade::STATUS);

            $table->index(Blade::RECEIVED);

            $table->index(Blade::CREATED_AT);

            $table->index(Blade::REFUND_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::BLADE, function($table)
        {
            $table->dropForeign(Table::BLADE . '_' . Blade::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::BLADE);
    }
}
