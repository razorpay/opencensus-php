<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\State\Entity;

class CreateBankingAccountState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_STATE, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ADMIN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::USER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BANKING_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->char(Entity::STATUS, 255);

            $table->string(Entity::SUB_STATUS, 64)
                  ->nullable();

            $table->char(Entity::BANK_STATUS, 255)
                  ->nullable();

            $table->string(Entity::ASSIGNEE_TEAM, 64)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::STATUS);

            $table->index(Entity::BANKING_ACCOUNT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_STATE);
    }
}
