<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Device\Entity;
use RZP\Models\Customer\Entity as Customer;

class CreateDevices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DEVICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::TYPE)
                  ->nullable();

            $table->string(Entity::OS)
                  ->nullable();

            $table->string(Entity::OS_VERSION)
                  ->nullable();

            $table->string(Entity::IMEI);

            $table->string(Entity::TAG)
                  ->nullable();

            $table->text(Entity::CHALLENGE)
                  ->nullable();

            $table->string(Entity::CAPABILITY)
                  ->nullable();

            $table->string(Entity::PACKAGE_NAME, 256)
                  ->nullable();

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::STATUS);

            $table->string(Entity::VERIFICATION_TOKEN, 100)
                  ->nullable()
                  ->unique();

            $table->string(Entity::AUTH_TOKEN, 100)
                  ->nullable()
                  ->unique();

            $table->string(Entity::UPI_TOKEN, 250)
                  ->nullable();

            $table->integer(Entity::VERIFIED_AT)
                  ->nullable();
            $table->integer(Entity::REGISTERED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CUSTOMER_ID);
            $table->index(Entity::CREATED_AT);

            $table->foreign(Entity::CUSTOMER_ID)
                  ->references(Customer::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::DEVICE, function($table)
        {
            $table->dropForeign(Table::DEVICE . '_' . Entity::CUSTOMER_ID . '_foreign');
        });

        Schema::drop(Table::DEVICE);
    }
}
