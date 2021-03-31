<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\QrCode\QrCodeConfig\Entity;

class CreateQrCodeConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR_CODE_CONFIG, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)
                  ->nullbale(false);

            $table->string(Entity::PAYMENT_METHOD)
                  ->nullable(false);

            $table->string(Entity::GATEWAY)
                  ->nullable(false);

            $table->string(Entity::PROVIDER)
                  ->nullable(false);

            $table->json(Entity::CONFIG)
                  ->nullable(false);

            $table->integer(Entity::DISABLED_AT);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::GATEWAY);

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
        Schema::dropIfExists(Table::QR_PAYMENT_REQUEST);
    }
}
