<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\Address\Entity;
use RZP\Models\Customer;

class CreateAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADDRESS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();
            $table->string(Entity::ENTITY_TYPE, 64)
                  ->nullable();

            $table->string(Entity::LINE_ONE, 1024);
            $table->string(Entity::LINE_TWO, 1024);
            $table->string(Entity::CITY, 128);
            $table->string(Entity::PINCODE, 32);
            $table->string(Entity::STATE, 128);
            $table->string(Entity::COUNTRY, 128);

            $table->string(Entity::ADDRESS_TYPE, 64);
            $table->tinyInteger(Entity::PRIMARY);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::DELETED_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADDRESS);
    }
}
