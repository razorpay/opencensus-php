<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Mandate\Patch\Entity;

class CreateP2pMandatePatchTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_MANDATE_PATCH , function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::MANDATE_ID, 14);

            $table->text(Entity::DETAILS);

            $table->string(Entity::ACTION, 50)->nullable();

            $table->string(Entity::STATUS, 50);

            $table->integer(Entity::EXPIRE_AT)
                  ->nullable();

            $table->string(Entity::ACTIVE, 50);

            $table->string(Entity::REMARKS, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT)->nullable();

            $table->integer(Entity::UPDATED_AT)
                  ->nullable();

            // Indices

            $table->index(Entity::MANDATE_ID);
            $table->index(Entity::ACTIVE);
            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists(Table::P2P_MANDATE_PATCH);
    }
}


