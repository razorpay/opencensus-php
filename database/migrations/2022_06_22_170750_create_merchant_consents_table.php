<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Merchant\Consent\Entity;
class CreateMerchantConsentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_CONSENTS, function(Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, Entity::ID_LENGTH);

            $table->char(Entity::REQUEST_ID, Entity::ID_LENGTH)->nullable();;

            $table->char(Entity::AUDIT_ID, Entity::ID_LENGTH);

            $table->json(Entity::METADATA)
                  ->nullable();

            $table->string(Entity::STATUS, 30)
                  ->nullable();

            $table->char(Entity::DETAILS_ID)
                  ->nullable();

            $table->string(Entity::CONSENT_FOR, 100)->nullable();

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

            $table->index(Entity::STATUS);

            $table->integer(Entity::RETRY_COUNT)
                  ->default(0);

            $table->char(Entity::ENTITY_TYPE, 32)->nullable();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_CONSENTS);
    }
}
