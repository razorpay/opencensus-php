<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccountStatement\Details\Entity;
use RZP\Models\BankingAccountStatement\Details\AccountType;

class CreateBankingAccountStatementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_STATEMENT_DETAILS, function (Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::ACCOUNT_NUMBER, Entity::ACCOUNT_NUMBER_LENGTH);

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::CHANNEL, 255);

            $table->string(Entity::ACCOUNT_TYPE, 20)
                  ->nullable()
                  ->default(AccountType::DIRECT);

            $table->string(Entity::STATUS, 255)
                  ->nullable();

            $table->bigInteger(Entity::STATEMENT_CLOSING_BALANCE)
                  ->nullable();

            $table->bigInteger(Entity::GATEWAY_BALANCE)
                  ->nullable();

            $table->integer(Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT)
                  ->nullable();

            $table->integer(Entity::GATEWAY_BALANCE_CHANGE_AT)
                  ->nullable();

            $table->integer(Entity::LAST_STATEMENT_ATTEMPT_AT)
                  ->nullable();

            $table->integer(Entity::BALANCE_LAST_FETCHED_AT)
                  ->nullable();

            $table->integer(Entity::LAST_RECONCILED_AT)
                  ->nullable();

            $table->string(Entity::PAGINATION_KEY)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->unique([Entity::ACCOUNT_NUMBER, Entity::CHANNEL]);

            $table->index([Entity::ACCOUNT_NUMBER, Entity::CHANNEL]);

            $table->index([Entity::LAST_RECONCILED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_STATEMENT_DETAILS);
    }
}
