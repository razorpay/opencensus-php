<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Models\Card;
use Constants\Table;

class CreateUnrecognizedCards extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UNRECOGNIZED_CARD, function(Blueprint $table)
        {
            $table->char('iin', 6)->primary();

            // Adds created_at and updated_at columns to the table
            $table->integer(Card\Unrecognized::CREATED_AT);
            $table->integer(Card\Unrecognized::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::UNRECOGNIZED_CARD);
    }

}
