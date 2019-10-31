<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Transaction\Entity;

class CreateP2pTransactionTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_TRANSACTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->string(Entity::PAYER_TYPE, 50);

            $table->string(Entity::PAYER_ID, Entity::ID_LENGTH);

            $table->string(Entity::PAYEE_TYPE, 50);

            $table->string(Entity::PAYEE_ID, Entity::ID_LENGTH);

            $table->string(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::METHOD, 50);

            $table->string(Entity::TYPE, 10);

            $table->string(Entity::FLOW, 10);

            $table->string(Entity::MODE, 50);

            $table->integer(Entity::AMOUNT);

            $table->integer(Entity::AMOUNT_MINIMUM)
                  ->nullable();

            $table->integer(Entity::AMOUNT_AUTHORIZED)
                  ->nullable();

            $table->string(Entity::CURRENCY, 10);

            $table->string(Entity::DESCRIPTION, 100)
                  ->nullable();

            $table->string(Entity::GATEWAY, 50);

            $table->string(Entity::STATUS, 50);

            $table->string(Entity::INTERNAL_STATUS, 50);

            $table->string(Entity::ERROR_CODE, 255)
                  ->nullable();

            $table->string(Entity::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::INTERNAL_ERROR_CODE, 255)
                  ->nullable();

            $table->string(Entity::PAYER_APPROVAL_CODE, 50)
                  ->nullable();

            $table->string(Entity::PAYEE_APPROVAL_CODE, 50)
                  ->nullable();

            $table->integer(Entity::INITIATED_AT)
                  ->nullable();

            $table->integer(Entity::EXPIRE_AT)
                  ->nullable();

            $table->integer(Entity::COMPLETED_AT)
                  ->nullable();

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index([Entity::DEVICE_ID, Entity::HANDLE, Entity::STATUS]);
            $table->index(Entity::CUSTOMER_ID);
            $table->index(Entity::STATUS);
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
        Schema::dropIfExists(Table::P2P_TRANSACTION);
    }
}


