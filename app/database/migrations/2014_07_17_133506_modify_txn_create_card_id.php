<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;
use Models\Card;

class ModifyTxnCreateCardId extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->char(Transaction::CARD_ID, Transaction::ID_LENGTH);

            $table->foreign(Transaction::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
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
        Schema::table(Table::TRANSACTION, function($table){

            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::CARD_ID.'_foreign');
        });
    }

}
