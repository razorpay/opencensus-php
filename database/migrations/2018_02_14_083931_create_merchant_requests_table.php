<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant\Request\Entity;
use RZP\Constants\Table;

class CreateMerchantRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_REQUEST, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 25);

            $table->string(Entity::STATUS, 30);

            $table->string(Entity::TYPE, 25);

            $table->string(Entity::PUBLIC_MESSAGE, 255)->nullable();

            $table->string(Entity::COMMENT, 255)->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::STATUS);

            $table->index(Entity::TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_REQUEST);
    }
}
