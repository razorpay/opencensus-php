<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\BankAccount\Bank\Entity;

class CreateP2pBankTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_BANK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::NAME, 100);

            $table->string(Entity::HANDLE, 50);

            $table->text(Entity::GATEWAY_DATA);

            $table->string(Entity::IFSC, 11)
                  ->nullable();

            $table->string(Entity::UPI_IIN, 6)
                  ->nullable();

            $table->string(Entity::UPI_FORMAT, 50)
                  ->nullable();

            $table->boolean(Entity::ACTIVE);

            $table->integer(Entity::REFRESHED_AT);

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
        Schema::dropIfExists(Table::P2P_BANK);
    }
}


