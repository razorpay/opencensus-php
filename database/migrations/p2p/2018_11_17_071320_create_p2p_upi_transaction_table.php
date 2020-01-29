<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Transaction\UpiTransaction\Entity;

class CreateP2pUpiTransactionTable extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create(Table::P2P_UPI_TRANSACTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::TRANSACTION_ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::DEVICE_ID, Entity::ID_LENGTH);

            $table->string(Entity::HANDLE, 50);

            $table->text(Entity::GATEWAY_DATA);

            $table->string(Entity::ACTION, 50);

            $table->string(Entity::STATUS, 50);

            $table->string(Entity::NETWORK_TRANSACTION_ID, 255)
                  ->nullable();

            $table->string(Entity::GATEWAY_TRANSACTION_ID, 255)
                  ->nullable();

            $table->string(Entity::GATEWAY_REFERENCE_ID, 255)
                  ->nullable();

            $table->string(Entity::RRN, 255)
                  ->nullable();

            $table->string(Entity::REF_ID, 50);

            $table->string(Entity::REF_URL, 50)
                  ->nullable();

            $table->char(Entity::MCC, 4)
                  ->nullable();

            $table->string(Entity::GATEWAY_ERROR_CODE, 255)
                  ->nullable();

            $table->string(Entity::GATEWAY_ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::RISK_SCORES, 255)
                  ->nullable();

            $table->string(Entity::PAYER_ACCOUNT_NUMBER, 50)
                  ->nullable();

            $table->string(Entity::PAYER_IFSC_CODE, 11)
                  ->nullable();

            $table->string(Entity::PAYEE_ACCOUNT_NUMBER, 50)
                  ->nullable();

            $table->string(Entity::PAYEE_IFSC_CODE, 20)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indices

            $table->index([Entity::DEVICE_ID, Entity::HANDLE]);
            $table->index(Entity::NETWORK_TRANSACTION_ID);
            $table->index(Entity::RRN);
            $table->index(Entity::GATEWAY_ERROR_CODE);
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
        Schema::dropIfExists(Table::P2P_UPI_TRANSACTION);
    }
}


