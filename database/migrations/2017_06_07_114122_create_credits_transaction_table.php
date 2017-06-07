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
        Schema::create(Table::CREDITS_TRANSACTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(CreditTransaction::TRANSACTION_ID, CreditTransaction::ID_LENGTH);

            $table->char(CreditTransaction::CREDIT_ID, CreditTransaction::ID_LENGTH);

            $table->integer(CreditTransaction::CREATED_AT);

            $table->integer(CreditTransaction::UPDATED_AT);

            $table->foreign(CreditTransaction::CREDIT_ID)
                ->references(Credits\Entity::ID)
                ->on(Table::CREDITS)
                ->on_delete('restrict');

            $table->foreign(CreditTransaction::TRANSACTION_ID)
                ->references(Transaction::ID)
                ->on(Table::TRANSACTION)
                ->on_delete('restrict');

            $table->unique([CreditTransaction::TRANSACTION_ID, CreditTransaction::CREDIT_ID]);
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
                Table::CREDITS_TRANSACTION.'_'.CreditTransaction::CREDIT_ID.'_foreign');

            $table->dropForeign(
                Table::CREDITS_TRANSACTION.'_'.CreditTransaction::TRANSACTION_ID.'_foreign');

            $table->dropUnique(
                [CreditTransaction::TRANSACTION_ID, CreditTransaction::CREDIT_ID]);
        });

        Schema::drop(Table::CREDITS_TRANSACTION);
    }
}
