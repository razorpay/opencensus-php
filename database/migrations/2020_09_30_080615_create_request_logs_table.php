<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\RequestLog\Entity;

class CreateRequestLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REQUEST_LOG, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable();

            $table->string(Entity::CLIENT_IP)->nullable();

            $table->string(Entity::PROXY_IP)->nullable();

            $table->string(Entity::ROUTE_NAME)->nullable();

            $table->string(Entity::REQUEST_METHOD)->nullable();

            $table->string(Entity::ENTITY_ID)->nullable();

            $table->string(Entity::ENTITY_TYPE)->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // indices
            $table->index(Entity::CREATED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
            $table->index(Entity::ENTITY_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::REQUEST_LOG);
    }
}
