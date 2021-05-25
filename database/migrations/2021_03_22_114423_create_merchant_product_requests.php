<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Merchant\Product\Entity as Product;
use RZP\Models\Merchant\Product\Request\Entity as Entity;

class CreateMerchantProductRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(TABLE::MERCHANT_PRODUCT_REQUEST, function (Blueprint $table){

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(ENTITY::MERCHANT_PRODUCT_ID, Entity::ID_LENGTH);

            $table->string(Entity::REQUESTED_ENTITY_TYPE, 255);

            $table->char(Entity::REQUESTED_ENTITY_ID, Entity::ID_LENGTH);

            $table->json(Entity::REQUESTED_CONFIG);

            $table->string(Entity::CONFIG_TYPE, 255);

            $table->string(Entity::STATUS, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_PRODUCT_ID);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_PRODUCT_REQUEST);
    }
}
