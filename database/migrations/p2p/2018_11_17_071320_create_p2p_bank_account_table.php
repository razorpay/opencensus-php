<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\BankAccount\Entity;

class CreateP2pBankAccountTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_BANK_ACCOUNT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::HANDLE, 50)
                  ->nullable();

            $table->text(Entity::GATEWAY_DATA);

            $table->string(Entity::BANK_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::IFSC, 11);

            $table->string(Entity::ACCOUNT_NUMBER, 50)
                  ->nullable();

            $table->string(Entity::MASKED_ACCOUNT_NUMBER, 50);

            $table->string(Entity::BENEFICIARY_NAME, 255)
                  ->nullable();

            $table->text(Entity::CREDS);

            $table->string(Entity::TYPE, 50)
                  ->nullable();

            $table->integer(Entity::REFRESHED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index([Entity::DEVICE_ID, Entity::HANDLE]);
            $table->index([Entity::ACCOUNT_NUMBER, Entity::IFSC]);
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
        Schema::dropIfExists(Table::P2P_BANK_ACCOUNT);
    }
}


