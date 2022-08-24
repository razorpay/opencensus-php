<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Mandate\Entity;

class CreateP2pMandateTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_MANDATE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::NAME, 50);

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->integer(Entity::AMOUNT);

            $table->string(Entity::AMOUNT_RULE, 50);

            $table->string(Entity::PAYER_ID, Entity::ID_LENGTH);

            $table->string(Entity::PAYEE_ID, Entity::ID_LENGTH);

            $table->string(Entity::PAYER_TYPE, 50);

            $table->string(Entity::PAYEE_TYPE, 50);

            $table->string(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CURRENCY, 3);

            $table->string(Entity::TYPE, 10);

            $table->string(Entity::FLOW, 10);

            $table->string(Entity::MODE, 50);

            $table->string(Entity::RECURRING_TYPE, 10);

            $table->string(Entity::RECURRING_VALUE, 10);

            $table->string(Entity::RECURRING_RULE, 10);

            $table->string(Entity::UMN, 50);

            $table->string(Entity::STATUS, 50);

            $table->string(Entity::INTERNAL_STATUS, 50);

            $table->integer(Entity::START_DATE);

            $table->integer(Entity::END_DATE);

            $table->string(Entity::ACTION, 50)->nullable();

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::GATEWAY, 50);

            $table->string(Entity::INTERNAL_ERROR_CODE, 255)
                  ->nullable();

            $table->string(Entity::ERROR_CODE, 255)
                  ->nullable();

            $table->string(Entity::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT)->nullable();

            $table->integer(Entity::COMPLETED_AT)->nullable();

            $table->integer(Entity::EXPIRE_AT)
                  ->nullable();

            $table->integer(Entity::UPDATED_AT)
                  ->nullable();

            $table->integer(Entity::REVOKED_AT)
                  ->nullable();

            $table->integer(Entity::PAUSE_START)->nullable();

            $table->integer(Entity::PAUSE_END)->nullable();;


            // Indices

            $table->index(Entity::DEVICE_ID);
            $table->index(Entity::UMN);
            $table->index(Entity::CUSTOMER_ID);
            $table->index(Entity::MERCHANT_ID);
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
        Schema::dropIfExists(Table::P2P_MANDATE);
    }
}


