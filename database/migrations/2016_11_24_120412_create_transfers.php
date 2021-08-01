<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Transfer\Entity;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Transfer;

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

            $table->char(Entity::STATUS, 255)
                  ->default(Transfer\Status::PROCESSED);

            $table->string(Entity::SETTLEMENT_STATUS, 30)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::TO_ID, Entity::ID_LENGTH);

            $table->string(Entity::TO_TYPE, 50);

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->integer(Entity::AMOUNT_REVERSED)
                  ->unsigned()
                  ->default(0);

            $table->text(Entity::NOTES);

            $table->integer(Entity::FEES)
                  ->unsigned()
                  ->default(0);

            $table->integer(Entity::TAX)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Entity::ON_HOLD)
                  ->default(0);

            $table->integer(Entity::ON_HOLD_UNTIL)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::RECIPIENT_SETTLEMENT_ID, Settlement\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::MESSAGE, 255)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::ERROR_CODE, 150)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::ORIGIN, 255)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::ACCOUNT_CODE, 255)
                  ->nullable()
                  ->default(null);

            $table->boolean(Entity::ACCOUNT_CODE_USED)
                  ->default(0);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::PROCESSED_AT)
                  ->nullable()
                  ->default(null);

            $table->tinyInteger(Entity::ATTEMPTS)
                  ->nullable()
                  ->default(0);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
            $table->index(Entity::SOURCE_ID);
            $table->index([Entity::SOURCE_TYPE, Entity::STATUS]);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Entity::RECIPIENT_SETTLEMENT_ID)
                  ->references(Settlement\Entity::ID)
                  ->on(Table::SETTLEMENT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment\Entity::TRANSFER_ID)
                  ->references(Entity::ID)
                  ->on(Table::TRANSFER)
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
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT . '_' . Payment\Entity::TRANSFER_ID . '_foreign');
        });

        Schema::table(Table::TRANSFER, function($table)
        {
            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::TRANSACTION_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::RECIPIENT_SETTLEMENT_ID . '_foreign'
            );
        });

        Schema::drop(Table::TRANSFER);
    }
}
