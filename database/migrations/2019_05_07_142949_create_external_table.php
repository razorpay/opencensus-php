<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\External\Entity;
use RZP\Models\Merchant\Balance;
use RZP\Models\BankingAccountStatement;

class CreateExternalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EXTERNAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Entity::TRANSACTION_ID, Transaction\Entity::ID_LENGTH)
                ->nullable();

            $table->string(Entity::CHANNEL, 255);

            $table->string(Entity::BANK_REFERENCE_NUMBER, 255);

            $table->string(Entity::UTR, 255)
                  ->nullable();

            $table->string(Entity::TYPE, 255);

            $table->string(Entity::REMARKS)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->char(Entity::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->char(Entity::BANK_ACCOUNT_STATEMENT_ID, BankingAccountStatement\Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            // Indexes

            $table->index(Entity::BANK_REFERENCE_NUMBER);

            $table->index(Entity::UTR);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::DELETED_AT);
            $table->index(Entity::CHANNEL);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            // Foreign Key relations

            $table->foreign(Entity::BALANCE_ID)
                  ->references(Balance\Entity::ID)
                  ->on(Table::BALANCE)
                  ->on_delete('restrict');

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::EXTERNAL, function($table)
        {
            $table->dropForeign(Table::EXTERNAL . '_' . Entity::BALANCE_ID . '_foreign');

            $table->dropForeign(Table::EXTERNAL . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::dropIfExists(Table::EXTERNAL);
    }
}
