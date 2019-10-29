<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Transaction\Concern\Entity;

class CreateP2pConcernsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::P2P_CONCERN, function (Blueprint $table) {
            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::TRANSACTION_ID, Entity::ID_LENGTH);

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->text(Entity::GATEWAY_DATA);

            $table->string(Entity::STATUS, 50);

            $table->string(Entity::INTERNAL_STATUS, 50);

            $table->string(Entity::COMMENT, 255);

            $table->string(Entity::GATEWAY_REFERENCE_ID, 255)
                  ->nullable();

            $table->string(Entity::RESPONSE_CODE, 50)
                  ->nullable();

            $table->string(Entity::RESPONSE_DESCRIPTION, 255)
                  ->nullable();

            $table->integer(Entity::CLOSED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index([Entity::DEVICE_ID, Entity::HANDLE]);
            $table->index([Entity::TRANSACTION_ID, Entity::CREATED_AT]);
            $table->index(Entity::STATUS);
            $table->index(Entity::GATEWAY_REFERENCE_ID);
            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::P2P_CONCERN);
    }
}
