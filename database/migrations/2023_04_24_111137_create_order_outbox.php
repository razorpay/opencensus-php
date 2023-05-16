<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\OrderOutbox\Entity as Entity;

class CreateOrderOutbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ORDER_OUTBOX, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::ORDER_ID, Entity::ID_LENGTH)->nullable(false);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable(false);

            $table->string(Entity::EVENT_NAME, 100)->nullable(false);

            $table->text(Entity::PAYLOAD)->nullable(false);

            $table->boolean(Entity::IS_DELETED)->default(false);

            $table->tinyInteger(Entity::RETRY_COUNT)->default(0);

            $table->bigInteger(Entity::CREATED_AT)->nullable(false);

            $table->bigInteger(Entity::UPDATED_AT)->nullable(false);

            $table->bigInteger(Entity::DELETED_AT)->nullable();

            $table->index(Entity::ORDER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::ORDER_OUTBOX);
    }
};
