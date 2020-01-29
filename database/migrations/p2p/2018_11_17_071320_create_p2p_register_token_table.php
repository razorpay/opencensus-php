<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Device\RegisterToken\Entity;

class CreateP2pRegisterTokenTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_REGISTER_TOKEN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::TOKEN, 50)
                  ->primary();

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::HANDLE, 50);

            $table->string(Entity::STATUS, 50);

            $table->text(Entity::DEVICE_DATA);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices
            $table->index([Entity::DEVICE_ID, Entity::HANDLE, Entity::STATUS]);
            $table->index(Entity::STATUS);
            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists(Table::P2P_REGISTER_TOKEN);
    }
}


