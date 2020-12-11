<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankTransferRequest\Entity;

class CreateBankTransferRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANK_TRANSFER_REQUEST, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::GATEWAY, 20)
                  ->nullable(false);

            $table->tinyInteger(Entity::IS_CREATED)
                  ->nullable()
                  ->default(0);

            $table->string(Entity::ERROR_MESSAGE)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::UTR);

            $table->string(Entity::MODE, 5)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYEE_NAME)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYEE_ACCOUNT, 40)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYEE_IFSC, 20)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_NAME)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_ACCOUNT, 40)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_IFSC, 20)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::AMOUNT)
                  ->nullable()
                  ->default(0);

            $table->text(Entity::DESCRIPTION)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::NARRATION)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::TIME)
                  ->nullable()
                  ->default(null);

            $table->json(Entity::REQUEST_PAYLOAD)
                  ->nullable(false);

            $table->json(Entity::REQUEST_SOURCE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::UTR);
            $table->index(Entity::PAYEE_ACCOUNT);
            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANK_TRANSFER_REQUEST);
    }
}
