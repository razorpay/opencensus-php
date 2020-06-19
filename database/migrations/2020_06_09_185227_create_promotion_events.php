<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Promotion\Event\Entity;
class CreatePromotionEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PROMOTION_EVENT, function(Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::NAME, 255);

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE1, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE2, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE3, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE4, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE5, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->unique(Entity::NAME);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PROMOTION_EVENT);
    }
}
