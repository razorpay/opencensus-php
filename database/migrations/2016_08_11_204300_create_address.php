<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Address\Entity;
use RZP\Models\Contact\Entity as Contact;

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
            $table->string(Entity::ENTITY_TYPE, 32)
                  ->nullable();

            $table->string(Entity::LINE1, 255);
            $table->string(Entity::LINE2, 255)
                  ->nullable();
            $table->string(Entity::CITY, 64)
                  ->nullable();
            $table->string(Entity::ZIPCODE, 16)
                  ->nullable();
            $table->string(Entity::STATE, 64)
                  ->nullable();
            $table->string(Entity::COUNTRY, 64);

            $table->string(Entity::TYPE, 32);
            $table->tinyInteger(Entity::PRIMARY);
            $table->string(Entity::CONTACT, 20)
                ->nullable();
            $table->string(Entity::NAME, 64)->nullable();
            $table->string(Entity::TAG, 32)->nullable();
            $table->string(Entity::LANDMARK, 255)->nullable();

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::COUNTRY);
            $table->index(Entity::STATE);
            $table->index(Entity::TYPE);
            $table->index(Entity::ENTITY_ID);
            $table->index(Entity::ENTITY_TYPE);
            $table->index(Entity::PRIMARY);

            $table->index(Entity::DELETED_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->char(Entity::SOURCE_ID, Entity::ID_LENGTH)
                  ->nullable();
            $table->string(Entity::SOURCE_TYPE, 32)
                  ->nullable();
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
