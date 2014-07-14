<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;

class AddLedgerIdAttribute extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->string(Transaction::LEDGER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropColumn(Transaction::LEDGER_ID);
        });
    }

}
