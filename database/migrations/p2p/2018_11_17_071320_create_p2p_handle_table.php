<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Vpa\Handle\Entity;

class CreateP2pHandleTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_HANDLE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::CODE, 50)
                  ->primary();

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::ACQUIRER, 50);

            $table->string(Entity::BANK, 11);

            $table->boolean(Entity::ACTIVE);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists(Table::P2P_HANDLE);
    }
}


