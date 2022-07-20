<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\Settlement\InternationalRepatriation\Entity as Entity;

class SettlementInternationalRepatriation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::SETTLEMENT_INTERNATIONAL_REPATRIATION, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable(false);

            $table->string(Entity::INTEGRATION_ENTITY, 20)->nullable(false);

            $table->string(Entity::PARTNER_MERCHANT_ID, 45)->nullable();

            $table->string(Entity::PARTNER_SETTLEMENT_ID, 45)->nullable();

            $table->string(Entity::PARTNER_TRANSACTION_ID, 45)->nullable();

            $table->integer(Entity::AMOUNT)->nullable(false);

            $table->integer(Entity::CREDIT_AMOUNT)->nullable(false);

            $table->string(Entity::CURRENCY, 3)->nullable(false);

            $table->string(Entity::CREDIT_CURRENCY, 3)->nullable(false);

            $table->json(Entity::SETTLEMENT_IDS)->nullable();

            $table->decimal(Entity::FOREX_RATE, 10, 6)->nullable();

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable();

            $table->integer(Entity::SETTLED_AT)->nullable();

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
        Schema::dropIfExists(Table::SETTLEMENT_INTERNATIONAL_REPATRIATION);
    }

}
