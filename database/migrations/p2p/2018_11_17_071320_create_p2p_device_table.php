<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Device\Entity;

class CreateP2pDeviceTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_DEVICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CONTACT, 20);

            $table->string(Entity::SIMID, 255);

            $table->string(Entity::UUID, 255);

            $table->string(Entity::TYPE, 255);

            $table->string(Entity::OS, 255);

            $table->string(Entity::OS_VERSION, 255);

            $table->string(Entity::APP_NAME, 255);

            $table->string(Entity::IP, 255);

            $table->string(Entity::GEOCODE, 255);

            $table->string(Entity::AUTH_TOKEN, 255);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index(Entity::CUSTOMER_ID);
            $table->index(Entity::CONTACT);
            $table->index(Entity::AUTH_TOKEN);
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
        Schema::dropIfExists(Table::P2P_DEVICE);
    }
}


