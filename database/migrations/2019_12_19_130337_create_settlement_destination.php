<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Destination\Entity as Destination;

class CreateSettlementDestination extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_DESTINATION, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Destination::ID, Destination::ID_LENGTH)
                  ->primary();

            $table->char(Destination::SETTLEMENT_ID, Destination::ID_LENGTH);

            $table->char(Destination::DESTINATION_TYPE, 255);

            $table->char(Destination::DESTINATION_ID, Destination::ID_LENGTH);

            $table->integer(Destination::CREATED_AT);

            $table->integer(Destination::UPDATED_AT);

            $table->integer(Destination::DELETED_AT)
                  ->nullable();

            $table->index(Destination::SETTLEMENT_ID);

            $table->index(Destination::DESTINATION_ID, Destination::DESTINATION_TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SETTLEMENT_DESTINATION);
    }
}
