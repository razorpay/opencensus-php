<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Device\DeviceToken\Entity;

class CreateP2pDeviceTokenTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_DEVICE_TOKEN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->text(Entity::GATEWAY_DATA);

            $table->string(Entity::STATUS, 50);

            $table->integer(Entity::REFRESHED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

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
        Schema::dropIfExists(Table::P2P_DEVICE_TOKEN);
    }
}


