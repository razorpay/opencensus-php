<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\P2p\BlackList\Entity;

class CreateP2pBlacklistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::P2P_BLACKLIST, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->string(Entity::CLIENT_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_ID, 255);

            $table->string(Entity::TYPE, 25);

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)->nullable();

            // Indices

            $table->index(Entity::CLIENT_ID);
            $table->index(Entity::ENTITY_ID);
            $table->index(Entity::TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::P2P_BLACKLIST);
    }
}
