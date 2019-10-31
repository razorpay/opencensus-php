<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Beneficiary\Entity;

class CreateP2pBeneficiaryTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_BENEFICIARY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, 255)
                  ->primary();

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 255);

            $table->string(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index(Entity::DEVICE_ID);
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
        Schema::dropIfExists(Table::P2P_BENEFICIARY);
    }
}


