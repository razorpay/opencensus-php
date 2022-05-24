<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Address\AddressConsent1cc\Entity;

class CreateAddressConsent1cc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADDRESS_CONSENT_1CC, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::CUSTOMER_ID, 20)
                ->nullable();
            $table->string(Entity::DEVICE_ID, 65)
                  ->nullable();

            $table->integer(Entity::DELETED_AT)
                  ->nullable();
            $table->integer(Entity::CREATED_AT);

            $table->index(Entity::CUSTOMER_ID);
            $table->index(Entity::DEVICE_ID);
            $table->index(Entity::DELETED_AT);
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
        Schema::drop(Table::ADDRESS_CONSENT_1CC);
    }
}
