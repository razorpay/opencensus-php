<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Receiver\Entity as Receiver;
use RZP\Constants\Table;

class CreateReceiversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::RECEIVER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Receiver::ID, Receiver::ID_LENGTH)
                  ->primary();

            $table->tinyInteger(Receiver::SINGLE_USE)
                  ->default(0);

            $table->tinyInteger(Receiver::VALID)
                  ->default(1);

            $table->integer(Receiver::EXPECTED_AMOUNT)
                  ->nullable();

            $table->tinyInteger(Receiver::ACCEPT_PARTIAL)
                  ->default(0);

            $table->integer(Receiver::AMOUNT_PAID)
                  ->default(0);

            $table->string(Receiver::ENTITY_TYPE);

            $table->char(Receiver::ENTITY_ID, Receiver::ID_LENGTH);

            $table->integer(Receiver::CREATED_AT);
            $table->integer(Receiver::UPDATED_AT);

            $table->integer(Receiver::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index(Receiver::ENTITY_ID);
            $table->index(Receiver::ENTITY_TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::RECEIVER);
    }
}
