<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use RZP\Constants\Table;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Customer\CustomerConsent1cc\Entity;

class CreateCustomerConsent1cc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CUSTOMER_CONSENT_1CC, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(UniqueIdEntity::ID, UniqueIdEntity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::CONTACT, 20)
                ->nullable();

            $table->string(Entity::MERCHANT_ID, 20)
                ->nullable();

            $table->boolean(Entity::STATUS)->default(false);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::CREATED_AT);

            $table->index([Entity::MERCHANT_ID, Entity::CONTACT]);
            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::CUSTOMER_CONSENT_1CC);
    }
}
