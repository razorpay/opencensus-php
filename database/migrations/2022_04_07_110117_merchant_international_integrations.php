<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\Merchant\InternationalIntegration\Entity as Entity;

class MerchantInternationalIntegrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_INTERNATIONAL_INTEGRATIONS, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable(false);

            $table->string(Entity::INTEGRATION_ENTITY, 20)->nullable();

            $table->string(Entity::INTEGRATION_KEY, 50)->nullable();

            $table->json(Entity::NOTES)->nullable();

            $table->json(Entity::BANK_ACCOUNT)->nullable();

            $table->string(Entity::REFERENCE_ID,50)->nullable();

            $table->json(Entity::PAYMENT_METHODS)->nullable();

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable();

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index([Entity::MERCHANT_ID, Entity::INTEGRATION_ENTITY], 'mii_index');

            $table->index(Entity::INTEGRATION_KEY);

            $table->index(Entity::REFERENCE_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_INTERNATIONAL_INTEGRATIONS);
    }
}
