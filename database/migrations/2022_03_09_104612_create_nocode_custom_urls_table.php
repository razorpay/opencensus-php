<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\PaymentLink\NocodeCustomUrl\Entity;

class CreateNocodeCustomUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::NOCODE_CUSTOM_URL, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->string(Entity::SLUG, Entity::SLUG_LEN);

            $table->string(Entity::DOMAIN, Entity::DOMAIN_LEN);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::PRODUCT, Entity::PRODUCT_LEN);

            $table->char(Entity::PRODUCT_ID, Entity::ID_LENGTH);

            $table->json(Entity::META_DATA);

            $table->integer(Entity::CREATED_AT)->unsigned();

            $table->integer(Entity::UPDATED_AT)->unsigned();

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index([Entity::PRODUCT_ID, Entity::MERCHANT_ID]);

            $table->unique([Entity::SLUG, Entity::DOMAIN]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::NOCODE_CUSTOM_URL);
    }
}
