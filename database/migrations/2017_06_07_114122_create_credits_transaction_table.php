<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Credits;
use RZP\Models\Promotion;
use RZP\Models\Merchant\Credits\Transaction as Transaction;

class CreateCreditsTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDITS_TRANSACTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Transaction::TRANSACTION_ID, Transaction\Entity::ID_LENGTH);

            $table->char(Transaction::CREDIT_ID, Transaction\Entity::ID_LENGTH);

            $table->integer(Transaction::CREATED_AT);

            $table->integer(Transaction::UPDATED_AT);

            $table->foreign(Transaction::CREDIT_ID)
                ->references(Credits\Entity::ID)
                ->on(Table::CREDITS)
                ->on_delete('restrict');

            $table->foreign(Transaction::TRANSACTION_ID)
                ->references(Models\Transaction\Entity::ID)
                ->on(Table::TRANSACTION)
                ->on_delete('restrict');

            $table->unique([Transaction::TRANSACTION_ID, Transaction::CREDIT_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::CREDITS_TRANSACTION, function (Blueprint $table)
        {
            $table->dropForeign(
                Table::CREDITS_TRANSACTION.'_'.Credits::CREDIT_ID.'_foreign');

            $table->dropForeign(
                Table::CREDITS_TRANSACTION.'_'.Credits::TRANSACTION_ID.'_foreign');

            $table->dropUnique(
                [Transaction::TRANSACTION_ID, Transaction::CREDIT_ID]);
        });

        Schema::drop(Table::CREDITS_TRANSACTION);
    }
}
