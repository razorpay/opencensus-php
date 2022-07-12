<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\Merchant\OwnerDetail\Entity;

class MerchantOwnerDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_OWNER_DETAILS, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable(false);

            $table->string(Entity::GATEWAY, 45)->nullable();

            $table->json(Entity::OWNER_DETAILS)->nullable();

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable();

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index([Entity::MERCHANT_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_OWNER_DETAILS);
    }
}
