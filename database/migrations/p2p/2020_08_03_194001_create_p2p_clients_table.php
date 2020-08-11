<?php

use RZP\Constants\Table;
use RZP\Models\P2p\Client\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateP2pClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::P2P_CLIENT, function (Blueprint $table) {

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::HANDLE, 50);

            $table->string(Entity::CLIENT_TYPE, 30);

            $table->string(Entity::CLIENT_ID, 50);

            $table->text(Entity::GATEWAY_DATA)
                  ->nullable();

            $table->text(Entity::SECRETS)
                  ->nullable();

            $table->text(Entity::CONFIG)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

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
        Schema::dropIfExists(Table::P2P_CLIENT);
    }
}
