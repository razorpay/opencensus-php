<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Transfer\Entity;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class CreateTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SOURCE_ID, Entity::ID_LENGTH);

            $table->string(Entity::SOURCE_TYPE, 50);

            $table->char(Entity::TO_ID, Entity::ID_LENGTH);

            $table->string(Entity::TO_TYPE, 50);

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->integer(Entity::AMOUNT_REVERSED)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Entity::ON_HOLD)
                  ->default(0);

            $table->integer(Entity::ON_HOLD_UNTIL)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
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
        Schema::table(Table::TRANSFER, function($table)
        {
            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::TRANSACTION_ID . '_foreign'
            );
        });

        Schema::table(Table::TRANSFER, function($table)
        {
            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::TRANSFER);
    }
}
