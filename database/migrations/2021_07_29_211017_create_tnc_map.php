<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant\Product\TncMap\Entity as Entity;

class CreateTncMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TNC_MAP, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::PRODUCT_NAME, 255)
                  ->unique();

            $table->json(Entity::CONTENT);

            $table->string(Entity::STATUS, 30);

            $table->string(Entity::BUSINESS_UNIT, 20)
                ->unique();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::TNC_MAP);
    }
}
