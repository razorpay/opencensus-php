<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\SubscriptionRegistration\Entity;

class CreateSubscriptionRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_REGISTRATION, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::METHOD, 255)
                  ->nullable();

            $table->string(Entity::ENTITY_TYPE, 255)
                  ->nullable();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 16)
                  ->nullable();

            $table->string(Entity::RECURRING_STATUS,255)
                  ->nullable();

            $table->string(Entity::FAILURE_REASON,255)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->default(0)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::ATTEMPTS)
                  ->default(0);

            $table->char(Entity::CURRENCY, 3)
                  ->nullable()
                  ->default('INR');

            $table->bigInteger(Entity::MAX_AMOUNT)
                  ->nullable();

            $table->string(Entity::FREQUENCY, 30)
                ->nullable();

            $table->string(Entity::AUTH_TYPE,255)
                  ->nullable();

            $table->bigInteger(Entity::EXPIRE_AT)
                  ->unsigned()
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::DELETED_AT);

            $table->index(Entity::EXPIRE_AT);

            $table->index(Entity::TOKEN_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SUBSCRIPTION_REGISTRATION);
    }
}
