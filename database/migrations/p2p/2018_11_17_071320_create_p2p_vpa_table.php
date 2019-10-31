<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Vpa\Entity;

class CreateP2pVpaTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_VPA, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, 255)
                  ->primary();

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::HANDLE, 50);

            $table->text(Entity::GATEWAY_DATA);

            $column = $table->string(Entity::USERNAME, 200);
            // Laravel's method collate does not work
            $column->collation = 'utf8mb4_unicode_ci';

            $table->string(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_NAME, 255)
                  ->nullable();

            $table->integer(Entity::PERMISSIONS)
                  ->nullable();

            $table->string(Entity::FREQUENCY, 50);

            $table->boolean(Entity::ACTIVE);

            $table->boolean(Entity::VALIDATED);

            $table->boolean(Entity::VERIFIED);

            $table->boolean(Entity::DEFAULT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index([Entity::DEVICE_ID, Entity::HANDLE]);
            $table->index([Entity::USERNAME, Entity::HANDLE]);
            $table->index(Entity::BANK_ACCOUNT_ID);
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
        Schema::dropIfExists(Table::P2P_VPA);
    }
}


