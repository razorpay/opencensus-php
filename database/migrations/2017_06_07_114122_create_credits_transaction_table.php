<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Credits;
use RZP\Models\Promotion;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\Merchant\Credits\Transaction\Entity as CreditTransaction;

class CreateCreditsTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDIT_TRANSACTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(CreditTransaction::ID, CreditTransaction::ID_LENGTH)
                  ->primary();

            $table->char(CreditTransaction::TRANSACTION_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->char(CreditTransaction::CREDITS_ID, Credits\Entity::ID_LENGTH);

            $table->char(CreditTransaction::ENTITY_ID, Credits\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(CreditTransaction::ENTITY_TYPE, 255)
                  ->nullable();

             $table->bigInteger(CreditTransaction::CREDITS_USED)
                   ->default(0);

            $table->integer(CreditTransaction::CREATED_AT);

            $table->integer(CreditTransaction::UPDATED_AT);

            $table->foreign(CreditTransaction::CREDITS_ID)
                  ->references(Credits\Entity::ID)
                  ->on(Table::CREDITS)
                  ->on_delete('restrict');

           $table->foreign(CreditTransaction::TRANSACTION_ID)
                 ->references(Transaction::ID)
                 ->on(Table::TRANSACTION)
                 ->on_delete('restrict');

            $table->unique([CreditTransaction::TRANSACTION_ID, CreditTransaction::CREDITS_ID]);

            $table->index(CreditTransaction::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::CREDIT_TRANSACTION, function (Blueprint $table)
        {
            $table->dropForeign(
                Table::CREDIT_TRANSACTION.'_'.CreditTransaction::CREDITS_ID.'_foreign');

            $table->dropForeign(
                Table::CREDIT_TRANSACTION.'_'.CreditTransaction::TRANSACTION_ID.'_foreign');

            $table->dropUnique(
                [CreditTransaction::TRANSACTION_ID, CreditTransaction::CREDITS_ID]);
        });

        Schema::drop(Table::CREDIT_TRANSACTION);
    }
}
