<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankTransferHistory\Entity;

class CreateBankTransferHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANK_TRANSFER_HISTORY, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::BANK_TRANSFER_ID, Entity::ID_LENGTH);

            $table->string(Entity::PAYER_NAME, 255)
                  ->nullable();

            $table->string(Entity::PAYER_ACCOUNT, 255)
                  ->nullable();

            $table->string(Entity::PAYER_IFSC, 255)
                  ->nullable();

            $table->char(Entity::PAYER_BANK_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::CREATED_BY, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::BANK_TRANSFER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANK_TRANSFER_HISTORY);
    }
}
