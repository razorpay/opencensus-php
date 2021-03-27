<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use RZP\Constants\Table;
use RZP\Models\WalletAccount\Entity;

class CreateWalletAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WALLET_ACCOUNT, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::ENTITY_TYPE, 40)
                  ->nullable();

            $table->string(Entity::PHONE, 13);

            $table->string(Entity::EMAIL, 100)
                  ->nullable();

            $table->string(Entity::NAME, 100)
                  ->nullable();

            $table->string(Entity::PROVIDER, 20);

            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index([Entity::PHONE, Entity::PROVIDER]);

            $table->index(Entity::MERCHANT_ID);

            $table->integer(Entity::FTS_FUND_ACCOUNT_ID)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::FTS_FUND_ACCOUNT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::WALLET_ACCOUNT);
    }
}
