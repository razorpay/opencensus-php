<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Address\Entity;
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

            $table->string(Entity::LINE1, 1024);
            $table->string(Entity::LINE2, 1024)
                  ->nullable();
            $table->string(Entity::CITY, 128)
                  ->nullable();
            $table->string(Entity::PINCODE, 32)
                  ->nullable();
            $table->string(Entity::STATE, 128);
            $table->string(Entity::COUNTRY, 128);

            $table->string(Entity::ADDRESS_TYPE, 64);
            $table->tinyInteger(Entity::PRIMARY);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::COUNTRY);
            $table->index(Entity::STATE);
            $table->index(Entity::ADDRESS_TYPE);
            $table->index(Entity::ENTITY_ID);
            $table->index(Entity::ENTITY_TYPE);
            $table->index(Entity::PRIMARY);
            
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
