<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use RZP\Constants\Table;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Pincode\ZipcodeDirectory\Entity;

class CreateZipcodeDirectory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ZIPCODE_DIRECTORY, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(UniqueIdEntity::ID, UniqueIdEntity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::ZIPCODE, 20)
                ->nullable();
            $table->string(Entity::COUNTRY, 65)
                  ->nullable();
            $table->string(Entity::STATE, 20)
                ->nullable();
            $table->string(Entity::STATE_CODE, 65)
                ->nullable();
            $table->string(Entity::CITY, 65)
                ->nullable();

            $table->integer(Entity::DELETED_AT)
                  ->nullable();
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::CREATED_AT);

            $table->index(Entity::COUNTRY);
            $table->index(Entity::ZIPCODE);
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
        Schema::drop(Table::ZIPCODE_DIRECTORY);
    }
}
