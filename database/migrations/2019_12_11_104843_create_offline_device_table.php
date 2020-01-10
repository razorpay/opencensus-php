<?php

use RZP\Constants\Table;
use RZP\Models\Offline\Device\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfflineDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::OFFLINE_DEVICE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::SERIAL_NUMBER);

            $table->string(Entity::FINGERPRINT)
                  ->nullable();

            $table->string(Entity::TYPE, 255);

            $table->string(Entity::MANUFACTURER);

            $table->string(Entity::MODEL);

            $table->string(Entity::OS, 255)
                  ->nullable();

            $table->string(Entity::FIRMWARE_VERSION, 255)
                  ->nullable();

            $table->string(Entity::FEATURES, 255)
                  ->nullable();

            $table->string(Entity::STATUS, 255);

            $table->string(Entity::PUSH_TOKEN, 255)
                  ->nullable();

            $table->string(Entity::ACTIVATION_TOKEN, 255)
                  ->nullable();

            $table->integer(Entity::ACTIVATED_AT)
                  ->nullable();

            $table->integer(Entity::LINKED_AT)
                  ->nullable();

            $table->integer(Entity::REGISTERED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->unique(Entity::PUSH_TOKEN);

            // Indices
            $table->index(Entity::ACTIVATION_TOKEN);
            $table->index(Entity::SERIAL_NUMBER);
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
        Schema::dropIfExists(Table::OFFLINE_DEVICE);
    }
}
